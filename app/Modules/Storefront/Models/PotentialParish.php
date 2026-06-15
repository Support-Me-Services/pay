<?php

namespace App\Modules\Storefront\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PotentialParish extends Model
{
    protected $table = 'potential_parishes';

    /** Statusy leada (lejek obdzwaniania): klucz => etykieta PL. */
    public const STATUSES = [
        'nowa'             => 'Nowa',
        'do_obdzwonienia'  => 'Do obdzwonienia',
        'zadzwoniono'      => 'Zadzwoniono',
        'zainteresowana'   => 'Zainteresowana',
        'odrzucona'        => 'Odrzucona',
        'dodana'           => 'Dodana',
    ];

    protected $fillable = [
        'name', 'city', 'voivodeship', 'denomination', 'phone', 'lat', 'lon',
        'status', 'salesperson_id', 'note', 'called_at',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lon' => 'decimal:7',
        'called_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'nowa',
    ];

    /** Etykieta statusu po polsku. */
    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? 'Nowa';
    }

    /** Kolory plakietki statusu [tło, tekst] — spójne z badge'ami parafii. */
    public function statusColors(): array
    {
        return match ($this->status) {
            'do_obdzwonienia' => ['#fdf3d7', '#9a7011'], // amber
            'zadzwoniono'     => ['#dbeafe', '#1e40af'], // niebieski
            'zainteresowana'  => ['#ede9fe', '#5b21b6'], // fiolet
            'dodana'          => ['#def0e4', '#1f7a4d'], // zielony
            'odrzucona'       => ['#fde2e1', '#991b1b'], // czerwony
            default           => ['#eef0f4', '#475467'], // nowa — szary
        };
    }

    /** Handlowiec prowadzący ten lead. */
    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(Salesperson::class, 'salesperson_id');
    }
}
