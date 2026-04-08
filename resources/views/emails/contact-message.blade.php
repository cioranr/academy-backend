@component('mail::message')
# Mesaj nou de contact

Ai primit un mesaj nou prin formularul de contact de pe site-ul Monza Ares Academy.

---

**Nume:** {{ $firstName }} {{ $lastName }}

**Email:** {{ $email }}

**Telefon:** {{ $phone ?: '—' }}

**Mesaj:**

{{ $message ?: '—' }}

---

@component('mail::button', ['url' => config('app.frontend_url') . '/admin/contact'])
Vezi toate mesajele
@endcomponent

@endcomponent
