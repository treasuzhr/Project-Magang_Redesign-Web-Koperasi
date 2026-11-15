<x-filament-panels::page>
 <x-filament::section heading="Ringkasan pembagian">
  <p>Status: {{ $period->status === 'draft' ? 'Draf' : 'Ditetapkan' }}. {{ $report ? 'Dihitung: '.$report['calculated_at'] : 'Parameter atau hasil historis belum memiliki laporan kalkulasi.' }}</p>
  <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr><th class="text-left p-3">Unit usaha</th><th class="text-right p-3">Pool</th><th class="text-right p-3">Total terbagi</th></tr></thead><tbody>
   @foreach($summary as $row)<tr><td class="p-3">{{ $row['label'] }}</td><td class="p-3 text-right">{{ $row['pool'] === null ? '-' : 'Rp '.number_format($row['pool'],0,',','.') }}</td><td class="p-3 text-right">Rp {{ number_format($row['distributed'],0,',','.') }}</td></tr>@endforeach
  </tbody></table></div>
  <p>Jasa Sipa Angg dihitung dari persentase tetap bunga dibayar, bukan diambil dari pool.</p>
  @if($report)<p>{{ $report['members'] }} anggota dihitung.</p><p>Porsi Jasa Sipa Angg: Rp {{ number_format($report['component_pools']['jasa_usipa_anggota'],0,',','.') }}. Hasil: Rp {{ number_format($report['distributed']['jasa_usipa_anggota'],0,',','.') }}.</p>@endif
 </x-filament::section>
 @if(!empty($report['warnings']))<x-filament::section heading="Peringatan"><ul class="list-disc pl-5">@foreach($report['warnings'] as $warning)<li>{{ $warning }}</li>@endforeach</ul></x-filament::section>@endif
 {{ $this->table }}
</x-filament-panels::page>
