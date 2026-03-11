<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable {
    use HasFactory, Notifiable, HasApiTokens;
    protected $fillable = ['name','first_name','last_name','email','password','phone','specialty','professional_grade','role'];
    protected $hidden = ['password','remember_token'];
    protected function casts(): array {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    }
    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isEventsManager(): bool { return in_array($this->role, ['admin','events_manager']); }
    public function isDoctor(): bool { return in_array($this->role, ['admin','doctor']); }
    public function registrations(): HasMany { return $this->hasMany(EventRegistration::class); }
    public function degrees(): HasMany { return $this->hasMany(Degree::class); }
    public function speakerProfiles(): HasMany { return $this->hasMany(EventSpeaker::class); }
}
