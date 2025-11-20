<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Affiche la page d'accueil
     */
    public function index()
    {
        return view('home');
    }

    /**
     * Affiche la page de connexion/compte
     */
    public function compte()
    {
        return view('compte');
    }
}
