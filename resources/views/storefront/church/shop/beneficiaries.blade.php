@extends('layouts.landing')

@section('title', 'Wspieramy — ' . config('shop.name'))
@section('meta-description', 'Kogo i jak wspieramy — SupportMe łączy ludzi, wartości i nowoczesne płatności.')

@push('head')
<style>
    .ben{ max-width:1080px; margin:0 auto; padding:36px 20px 72px; }
    .ben__head{ text-align:center; margin-bottom:36px; }
    .ben__head h1{ font-size:34px; margin:0; }

    .ben-node{ display:grid; gap:40px; align-items:center; padding:32px 0; border-bottom:1px solid #eef1f4; }
    .ben-node:last-child{ border-bottom:0; }
    .ben-node--left{ grid-template-columns:auto 1fr; }
    .ben-node--right{ grid-template-columns:1fr auto; }
    .ben-node--right .ben-node__media{ order:2; }
    .ben-node--noimg{ grid-template-columns:1fr; }

    /* Grafika w kole — cała widoczna (contain), wypełnienie tła, wyśrodkowana */
    .ben-node__media{ justify-self:center; width:280px; height:280px; border-radius:50%; overflow:hidden; background:#eef2f7; position:relative; }
    .ben-node__media img{ position:absolute; top:50%; left:50%; width:100%; height:100%; object-fit:contain; display:block; transform-origin:center; }

    .ben-node__heading{ font-size:26px; margin:0 0 12px; }
    .ben-node__text{ font-size:16px; line-height:1.6; color:#3f4a58; }
    .ben-node__text p{ margin:0 0 12px; }
    .ben-node__text img{ max-width:100%; height:auto; border-radius:10px; }
    .ben-node__text a{ color:#2563eb; }
    .ben__empty{ text-align:center; color:#6b7280; padding:40px 0; }

    /* Mobile: grafika ZAWSZE nad nagłówkiem, niezależnie od ustawienia strony */
    @media (max-width:720px){
        .ben-node--left, .ben-node--right, .ben-node--noimg{ grid-template-columns:1fr; gap:20px; }
        .ben-node--right .ben-node__media{ order:0; }
        .ben-node__media{ width:220px; height:220px; }
        /* Mobile jak w Figmie: grafika nad nagłówkiem, całość wyśrodkowana */
        .ben-node__body{ text-align:center !important; }
    }
</style>
@endpush

@section('content')
<main class="ben">
    <div class="ben__head">
        <h1>Wspieramy</h1>
    </div>

    @forelse($nodes as $node)
        @php $variant = $node->image ? ($node->imageRight() ? 'right' : 'left') : 'noimg'; @endphp
        <section class="ben-node ben-node--{{ $variant }}">
            @if($node->image)
                <div class="ben-node__media">
                    <img src="{{ asset('storage/' . $node->image) }}" alt="{{ $node->heading }}"
                         style="transform: translate(-50%,-50%) translate({{ $node->image_x }}%, {{ $node->image_y }}%) scale({{ $node->image_scale / 100 }});">
                </div>
            @endif
            <div class="ben-node__body" style="text-align: {{ $node->text_align }}">
                <h2 class="ben-node__heading">{{ $node->heading }}</h2>
                <div class="ben-node__text">{!! $node->body_html !!}</div>
            </div>
        </section>
    @empty
        <p class="ben__empty">Wkrótce więcej informacji o tym, kogo wspieramy.</p>
    @endforelse
</main>
@endsection
