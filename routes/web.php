<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
});

Volt::route('/reservations/delete', 'reservations.delete')->name('reservations.delete');
Volt::route('/reservations/insert', 'reservations.insert')->name('reservations.insert');
Volt::route('/reservations/update', 'reservations.update')->name('reservations.update');
Volt::route('/reservations/select', 'reservations.select')->name('reservations.select');

