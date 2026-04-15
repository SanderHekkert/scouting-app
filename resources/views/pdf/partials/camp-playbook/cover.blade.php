<div class="cover-page">
    <div class="cover-main">
        <div class="cover-photo-wrap">
            <div>
                @if(!empty($coverPhotoDataUri))
                    <img src="{{ $coverPhotoDataUri }}" alt="Cover foto kampdraaiboek" class="cover-photo">
                @else
                    <div class="cover-photo-fallback">DRAAIBOEK</div>
                @endif
            </div>
        </div>
        <div class="cover-meta">
        <p class="cover-title">{{ $playbook->title }}</p>
        <p class="cover-subtitle">Kampdraaiboek - Fridtjof Nansen Groep 12</p>

        <div class="cover-header-card">
            <table class="cover-header-table">
                <tr>
                    <td style="width:76px;">
                        @if(!empty($logoDataUri))
                            <img src="{{ $logoDataUri }}" alt="Fridtjof Nansen Groep 12" class="logo">
                        @else
                            <div class="logo-fallback">FN12</div>
                        @endif
                    </td>
                    <td>
                        <div>
                            <span class="pill">Jaar: {{ (int) $playbook->camp_year }}</span>
                            <span class="pill">Speltak: {{ ucfirst(str_replace('_', ' ', (string) $playbook->section)) }}</span>
                            <span class="pill">Kamptype: {{ (string) data_get($playbook->meta, 'camp_location', 'fram') === 'clubhuis' ? 'Clubhuis' : 'Fram' }}</span>
                            @if(trim((string) data_get($playbook->meta, 'camp_place', '')) !== '')
                                <span class="pill">Plaats: {{ (string) data_get($playbook->meta, 'camp_place', '') }}</span>
                            @endif
                            @if(trim((string) data_get($playbook->meta, 'camp_dates', '')) !== '')
                                <span class="pill">Datum: {{ (string) data_get($playbook->meta, 'camp_dates', '') }}</span>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    </div>
</div>
