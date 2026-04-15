<div class="toc-page">
    <p class="toc-title">Inhoud</p>
    <p class="toc-subtitle">Overzicht van hoofdstukken en startpagina's.</p>
    @if($tocEntries !== [])
        <table class="toc-table">
            <thead>
                <tr>
                    <th>Hoofdstuk</th>
                    <th class="toc-page-number">Pagina</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tocEntries as $entry)
                    <tr>
                        <td>{{ (string) ($entry['title'] ?? '') }}</td>
                        <td class="toc-page-number">{{ (int) ($entry['page'] ?? 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>Geen hoofdstukken gevonden voor de inhoudspagina.</p>
    @endif
</div>
