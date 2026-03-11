<x-mail::message>
# Bună ziua, {{ $firstName }}!

Înregistrarea dumneavoastră la evenimentul **{{ $event->title }}** a fost primită cu succes.

---

**Detalii eveniment:**

- 📅 **Data:** {{ \Carbon\Carbon::parse($event->date)->locale('ro')->isoFormat('D MMMM YYYY') }}
@if($event->time_start)
- 🕐 **Ora:** {{ $event->time_start }}{{ $event->time_end ? ' – ' . $event->time_end : '' }}
@endif
@if($event->location)
- 📍 **Locație:** {{ $event->location }}{{ $event->venue ? ', ' . $event->venue : '' }}
@endif
@if($event->credits)
- 🎓 **Credite EMC:** {{ $event->credits }} puncte
@endif

Cererea dumneavoastră este **în curs de analizare**. Veți fi contactat în curând cu confirmarea participării.

---

@if($isNewAccount)
Un cont a fost creat automat pentru dumneavoastră pe platforma **Monza Ares Academy**.

**Date de autentificare:**
- **Email:** {{ $email }}
- **Parolă temporară:** `{{ $generatedPassword }}`

<x-mail::button :url="$loginUrl" color="blue">
Accesați contul dumneavoastră
</x-mail::button>

> Vă recomandăm să schimbați parola după prima autentificare din secțiunea **Profilul meu**.

@else

<x-mail::button :url="$dashboardUrl" color="blue">
Vizualizați înregistrările mele
</x-mail::button>

@endif

---

Cu stimă,
**Echipa Monza Ares Academy**
</x-mail::message>
