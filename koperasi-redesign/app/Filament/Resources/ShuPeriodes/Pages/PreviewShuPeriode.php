<?php

namespace App\Filament\Resources\ShuPeriodes\Pages;

use App\AdminShu;
use App\Filament\Resources\ShuPeriodes\ShuPeriodeResource;
use App\Models\ShuPeriode;
use App\ShuCalculator;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PreviewShuPeriode extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = ShuPeriodeResource::class;

    protected string $view = 'filament.shu.preview';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        abort_unless(auth()->user()?->role === 'admin', 403);
    }

    public function getTitle(): string
    {
        return 'Pratinjau SHU '.$this->getRecord()->tahun;
    }

    public function table(Table $table): Table
    {
        $columns = [TextColumn::make('nama')->label('Anggota')->searchable(['anggotas.nama']), TextColumn::make('nip')->label('NIP/NRP')->searchable(query: fn (Builder $query, string $search) => $query->where('users.nip', 'like', '%'.$search.'%'))];
        foreach (ShuCalculator::LABELS + ['total' => 'Total'] as $code => $label) {
            $columns[] = TextColumn::make($code)->label($label)->alignEnd()->formatStateUsing(fn ($state): string => 'Rp '.number_format((int) $state, 0, ',', '.'))->summarize(Sum::make()->label('Total')->formatStateUsing(fn ($state): string => 'Rp '.number_format((int) $state, 0, ',', '.')));
        }

        return $table->query(fn () => app(ShuCalculator::class)->previewQuery($this->getRecord()))->columns($columns)->defaultSort('anggotas.id')->defaultPaginationPageOption(25)->emptyStateHeading('Belum ada hasil SHU')->emptyStateDescription('Isi parameter lalu hitung SHU untuk melihat hasil per anggota.');
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Ubah parameter')->record(fn () => $this->getRecord())->url(fn () => ShuPeriodeResource::getUrl('edit', ['record' => $this->getRecord()]))->visible(fn (): bool => $this->getRecord()->status === 'draft'),
            Action::make('calculate')->label('Hitung SHU')->requiresConfirmation()->modalHeading(fn (): string => 'Hitung SHU tahun '.$this->getRecord()->tahun)->modalDescription('Hasil draf sebelumnya akan diganti berdasarkan parameter dan transaksi saat ini.')->visible(fn (): bool => $this->getRecord()->status === 'draft')->action(function (): void {
                abort_unless(auth()->user()?->role === 'admin', 403);
                try {
                    $this->record = app(ShuCalculator::class)->calculate($this->getRecord());
                    $this->resetTable();
                    Notification::make()->title('SHU berhasil dihitung')->success()->send();
                } catch (ValidationException $e) {
                    Notification::make()->title('SHU belum dapat dihitung')->body(collect($e->errors())->flatten()->join(' '))->danger()->send();
                }
            }),

            Action::make('establish')->label('Tetapkan')->color('success')->requiresConfirmation()
                ->modalHeading(fn (): string => 'Tetapkan SHU tahun '.$this->getRecord()->tahun)
                ->modalDescription(fn (): string => 'Total Rp '.number_format((int) $this->getRecord()->shu_bersih, 0, ',', '.').'. Parameter dan hasil akan terkunci setelah ditetapkan.')
                ->visible(fn (): bool => $this->getRecord()->status === 'draft' && ! empty($this->getRecord()->parameter['report']['calculated_at']))
                ->action(function (): void {
                    try {
                        $this->record = app(AdminShu::class)->establish($this->getRecord());
                        Notification::make()->title('SHU berhasil ditetapkan')->success()->send();
                    } catch (ValidationException $e) {
                        Notification::make()->title('SHU belum dapat ditetapkan')->body(collect($e->errors())->flatten()->join(' '))->danger()->send();
                    }
                }),
            Action::make('csv')->label('Unduh CSV')->visible(fn (): bool => $this->getRecord()->shuAnggotas()->exists())->action(fn (): StreamedResponse => $this->downloadCsv()),
        ];
    }

    public function downloadCsv(): StreamedResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        $period = ShuPeriode::findOrFail($this->getRecord()->id);

        return response()->streamDownload(function () use ($period): void {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, ['Anggota', 'NIP/NRP', ...array_values(ShuCalculator::LABELS), 'Total'], ',', '"', '');
            $totals = array_fill_keys([...array_keys(ShuCalculator::LABELS), 'total'], 0);
            foreach (app(ShuCalculator::class)->previewQuery($period)->orderBy('anggotas.id')->cursor() as $member) {
                $name = preg_match('/^[=+@\-\t\r]/u', $member->nama) ? "'".$member->nama : $member->nama;
                // Leading apostrophe prevents spreadsheet formula execution and preserves 18-digit NIP.
                $values = [$name, "'".$member->nip];
                foreach ($totals as $key => $unused) {
                    $value = (int) $member->$key;
                    $values[] = $value;
                    $totals[$key] += $value;
                }
                fputcsv($file, $values, ',', '"', '');
            }
            fputcsv($file, ['Total keseluruhan', '', ...array_values($totals)], ',', '"', '');
            fclose($file);
        }, 'shu-'.$period->tahun.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function getViewData(): array
    {
        $period = ShuPeriode::findOrFail($this->getRecord()->id);
        $report = $period->parameter['report'] ?? null;
        $totals = $period->shuAnggotas()->select('kode_komponen')->selectRaw('SUM(shu) AS amount')->groupBy('kode_komponen')->pluck('amount', 'kode_komponen');
        $summary = [];
        foreach (AdminShu::POOLS as $unit => $label) {
            $codes = match ($unit) {
                'toko' => ['simpanan_pokok_wajib', 'belanja'],'usipa' => ['simpanan_sukarela', 'jasa_usipa_anggota'],'usipa_khusus' => ['jasa_usipa_khusus'],default => ['jasa_umum']
            };
            $summary[] = ['label' => $label, 'pool' => $period->parameter['pool_'.$unit] ?? null, 'distributed' => array_sum(array_map(fn (string $code): int => (int) ($totals[$code] ?? 0), $codes))];
        }

        return ['period' => $period, 'report' => $report, 'summary' => $summary];
    }
}
