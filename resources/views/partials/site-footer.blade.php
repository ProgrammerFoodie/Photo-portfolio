<footer>
    <div class="container text-center">
        {{ str_replace('{year}', now()->year, \App\Models\Setting::get('footer_text')) }}
    </div>
</footer>
