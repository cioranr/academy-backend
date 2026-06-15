<x-mail::message>
# Bună ziua, {{ $firstName }}!

Felicitări! Diploma dumneavoastră de participare la evenimentul **{{ $event->title }}** a fost generată.

O găsiți atașată acestui email și este disponibilă și în secțiunea **Contul meu** de pe platformă.

<x-mail::button :url="$dashboardUrl" color="monza">
Accesați contul meu
</x-mail::button>

---

Cu stimă,
**Echipa Monza Ares Academy**
</x-mail::message>
