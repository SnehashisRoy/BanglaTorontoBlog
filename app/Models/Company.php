<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = ['company', 'contact', 'service', 'description', 'slug', 'company_slug'];

    public const CATEGORY_LABELS = [
        'real-estate-agent'  => 'Real Estate',
        'mortgage'           => 'Mortgage',
        'law-immigration'    => 'Law & Immigration',
        'law-office'         => 'Law Office',
        'tax-return'         => 'Tax & Accounting',
        'insurance'          => 'Insurance',
        'money-exchange'     => 'Money Exchange',
        'dental-clinic'      => 'Dental Clinic',
        'doctor'             => 'Doctor',
        'pharmacy'           => 'Pharmacy',
        'homeopath'          => 'Homeopath',
        'house-repair'       => 'House Repair',
        'auto-repair'        => 'Auto Repair',
        'buy-cars'           => 'Buy Cars',
        'rental'             => 'Van Rental',
        'mover'              => 'Movers',
        'travel-agents'      => 'Travel Agents',
        'restaurant-party'   => 'Restaurant & Party',
        'catering'           => 'Catering',
        'grocery'            => 'Grocery',
        'shopping'           => 'Shopping',
        'driving'            => 'Driving School',
        'tutorial'           => 'Tutorial',
        'computer-graphics'  => 'Computer & Graphics',
        'rent-house'         => 'Rent House',
    ];

    public const CATEGORY_ICONS = [
        'real-estate-agent'  => '🏠',
        'mortgage'           => '🏦',
        'law-immigration'    => '⚖️',
        'law-office'         => '⚖️',
        'tax-return'         => '📊',
        'insurance'          => '🛡️',
        'money-exchange'     => '💱',
        'dental-clinic'      => '🦷',
        'doctor'             => '🩺',
        'pharmacy'           => '💊',
        'homeopath'          => '🌿',
        'house-repair'       => '🔧',
        'auto-repair'        => '🔩',
        'buy-cars'           => '🚗',
        'rental'             => '🚐',
        'mover'              => '📦',
        'travel-agents'      => '✈️',
        'restaurant-party'   => '🎉',
        'catering'           => '🍽️',
        'grocery'            => '🛒',
        'shopping'           => '🛍️',
        'driving'            => '🚘',
        'tutorial'           => '📚',
        'computer-graphics'  => '💻',
        'rent-house'         => '🏘️',
    ];

    public function categoryLabel(): string
    {
        return self::CATEGORY_LABELS[$this->slug] ?? ucwords(str_replace('-', ' ', $this->slug));
    }

    public function categoryIcon(): string
    {
        return self::CATEGORY_ICONS[$this->slug] ?? '🏢';
    }
}
