@extends('layouts.panel')

@section('title', 'Tagi — ' . $shop->name)

@section('content')
    <div class="panel-title">
        <h1>Tagi: {{ $shop->name }}</h1>
        <a href="{{ route('panel.tags.create', $shop) }}" class="btn btn-primary btn-sm">+ Dodaj tag</a>
    </div>

    <div class="card card-static">
        <div class="card-body">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>UID taga</th><th>Etykieta</th><th>Prowadzi do</th><th>Otwarcia</th><th>Zakupy</th><th>Status</th><th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($tags as $row)
                        <tr>
                            <td class="fw-bold"><code>{{ $row['tag']->tag_uid }}</code></td>
                            <td>{{ $row['tag']->label ?: '—' }}</td>
                            <td><a href="{{ $row['tag']->target_url }}" target="_blank">{{ $row['tag']->target_url }}</a></td>
                            <td>{{ $row['stats']['opens'] }}</td>
                            <td>{{ $row['stats']['paid'] }}</td>
                            <td>
                                @if($row['tag']->active)
                                    <span class="badge badge-success">aktywny</span>
                                @else
                                    <span class="badge badge-muted">nieaktywny</span>
                                @endif
                            </td>
                            <td class="actions nowrap">
                                <a href="{{ route('panel.tags.edit', [$shop, $row['tag']]) }}">Edytuj</a>
                                <a href="{{ route('panel.stats', ['shop_id' => $shop->id, 'tag_id' => $row['tag']->id]) }}">Statystyki</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted">Brak tagów w tym sklepie.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
