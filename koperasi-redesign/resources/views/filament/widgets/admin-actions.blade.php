<x-filament-widgets::widget>
    <x-filament::section heading="Perlu tindakan">
        <div class="primkop-action-lists">
            <section aria-labelledby="admin-pending-loans"><h3 id="admin-pending-loans">Pengajuan pinjaman</h3><ul>
                @forelse($actions['loans'] as $loan)
                    <li><a href="{{ \App\Filament\Resources\Pinjamans\PinjamanResource::getUrl('view', ['record'=>$loan]) }}"><span><strong>{{ $loan->anggota->nama }}</strong><small>{{ $loan->kode }} &middot; {{ $loan->tanggal_pengajuan->locale('id')->translatedFormat('d M Y') }}</small></span><b>Rp {{ number_format($loan->jumlah,0,',','.') }}</b></a></li>
                @empty<li class="primkop-action-empty">Tidak ada yang perlu diproses</li>@endforelse
            </ul></section>
            <section aria-labelledby="admin-new-orders"><h3 id="admin-new-orders">Pesanan baru</h3><ul>
                @forelse($actions['orders'] as $order)
                    <li><a href="{{ \App\Filament\Resources\Pesanans\PesananResource::getUrl('view', ['record'=>$order]) }}"><span><strong>{{ $order->anggota->nama }}</strong><small>{{ $order->kode }} &middot; {{ $order->tanggal->locale('id')->translatedFormat('d M Y') }}</small></span><b>Rp {{ number_format($order->total,0,',','.') }}</b></a></li>
                @empty<li class="primkop-action-empty">Tidak ada yang perlu diproses</li>@endforelse
            </ul></section>
            <section aria-labelledby="admin-overdue"><h3 id="admin-overdue">Angsuran lewat jatuh tempo</h3><ul>
                @forelse($actions['installments'] as $installment)
                    <li><a href="{{ \App\Filament\Resources\Pinjamans\PinjamanResource::getUrl('view', ['record'=>$installment->pinjaman]) }}"><span><strong>{{ $installment->pinjaman->anggota->nama }}</strong><small>{{ $installment->pinjaman->kode }} &middot; Angsuran {{ $installment->angsuran_ke }} &middot; {{ $installment->jatuh_tempo->locale('id')->translatedFormat('d M Y') }}</small></span><b>Rp {{ number_format($installment->total,0,',','.') }}</b></a></li>
                @empty<li class="primkop-action-empty">Tidak ada yang perlu diproses</li>@endforelse
            </ul></section>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
