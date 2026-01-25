@extends('laravel-analyzer::layout')

@section('content')
    <div class="container py-4">
        <h1 class="mb-4">Translations Editor</h1>

        @if(session('status'))
            <div class="alert alert-success mb-3">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('la.translations.store') }}">
            @csrf

            @foreach($translations->translations as $fileGroup)
                @foreach($fileGroup->translationFiles as $file)
                    <div class="card mb-4">
                        <div class="card-header">
                            {{ $file->filename }}
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th style="width:25%">Key</th>
                                    @foreach($translations->translations as $localeGroup)
                                        <th>{{ $localeGroup->locale }}</th>
                                    @endforeach
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($file->translations as $line)
                                    <tr>
                                        <td class="text-monospace">{{ $line->key }}</td>

                                        @foreach($translations->translations as $localeGroup)
                                            @php
                                                $localeFile = $localeGroup->translationFiles->first(fn ($f) => $f->filename === $file->filename);
                                                $localeLine = $localeFile?->translations->first(fn ($l) => $l->key === $line->key);
                                            @endphp

                                            <td>
                                                <input
                                                        type="text"
                                                        class="form-control form-control-sm {{ $localeLine && ! $localeLine->exists ? 'border-warning' : '' }}"
                                                        name="translations[{{ $localeGroup->locale }}][{{ $file->filename }}][{{ $line->key }}]"
                                                        value="{{ $localeLine?->translation }}"
                                                >
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            @endforeach

            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    Save translations
                </button>
            </div>
        </form>
    </div>
@endsection
