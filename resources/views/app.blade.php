<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
        
        <script>
            // Initialize theme as early as possible to prevent flash of white
            (function() {
                const theme = localStorage.getItem('theme') || 'system';
                const isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            })();
        </script>
    </head>
    <body class="font-sans antialiased">
        @inertia
        <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
        <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-database-compat.js"></script>
        
        <script>
            // Firebase Configuration
            const firebaseConfig = {
                apiKey: "AIzaSyC7MPieor9OrzeAXgkMOAPmPJSlcdZ4ow0",
                authDomain: "wattwise-1d764.firebaseapp.com",
                databaseURL: "https://wattwise-1d764-default-rtdb.firebaseio.com",
                projectId: "wattwise-1d764",
                storageBucket: "wattwise-1d764.firebasestorage.app",
                messagingSenderId: "982168524343",
                appId: "1:982168524343:web:a3d97e752a9d5e2fb73e5e"
            };

            // Initialize Firebase
            firebase.initializeApp(firebaseConfig);
            
            // Create global database reference
            window.db = firebase.database();
            window.dispatchEvent(new Event('firebase-ready'));
            
            console.log('Firebase initialized successfully');
        </script>
    </body>
</html>
