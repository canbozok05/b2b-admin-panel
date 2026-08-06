<x-mail::message>
# Sipariş Durumu Güncellendi

Merhaba {{ $order->customer->name }},

**{{ $order->order_number }}** numaralı siparişinizin durumu güncellendi.

Yeni durum: **{{ $order->status }}**

Toplam tutar: {{ $order->total_amount }} ₺

<x-mail::button :url="''">
Siparişi Görüntüle
</x-mail::button>

Teşekkürler,<br>
{{ config('app.name') }}
</x-mail::message>
