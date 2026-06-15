@extends('layouts.panel')

@section('title', 'Kategorie')

@section('content')
    <div class="panel-title">
        <h1>Kategorie wsparcia</h1>
        <a href="{{ route('panel.categories.create') }}" class="btn btn-primary btn-sm">+ Dodaj kategorię</a>
    </div>

    <div class="card card-static">
        <div class="card-body">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Nazwa</th><th>Ikonka</th><th>Slug</th><th>Źródło</th><th>Kolejność</th><th>Status</th><th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($tree as $row)
                        @php($cat = $row['cat'])
                        <tr>
                            <td class="fw-bold">
                                @if($row['depth'] > 0)
                                    <span class="text-muted">{!! str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $row['depth']) !!}↳ </span>
                                @endif
                                {{ $cat->label_text }}
                            </td>
                            <td>
                                @if($cat->icon)
                                    <img src="{{ asset('storage/' . $cat->icon) }}" alt=""
                                         style="width:32px;height:32px;border-radius:50%;object-fit:cover">
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td><span class="text-muted">{{ $cat->slug }}</span></td>
                            <td>{{ $cat->sourceLabel() }}</td>
                            <td class="nowrap">
                                {{ $cat->position }}
                                <form method="POST" action="{{ route('panel.categories.reorder', $cat) }}" style="display:inline">
                                    @csrf
                                    <button type="submit" name="direction" value="up" class="btn-link" title="W górę">↑</button>
                                    <button type="submit" name="direction" value="down" class="btn-link" title="W dół">↓</button>
                                </form>
                            </td>
                            <td>
                                @if($cat->active)
                                    <span class="badge badge-success">aktywna</span>
                                @else
                                    <span class="badge badge-muted">ukryta</span>
                                @endif
                            </td>
                            <td class="actions nowrap">
                                <a href="{{ route('panel.categories.edit', $cat) }}">Edytuj</a>
                                <form method="POST" action="{{ route('panel.categories.destroy', $cat) }}" style="display:inline"
                                      onsubmit="return confirm('Usunąć kategorię? Podkategorie staną się głównymi.');">
                                    @csrf
                                    @method('DELETE')
                                    <a href="#" onclick="this.closest('form').submit(); return false;">Usuń</a>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted">Brak kategorii.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
