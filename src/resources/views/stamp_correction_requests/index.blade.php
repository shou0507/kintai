@extends('layouts.admin')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/requests.css') }}?v={{ time() }}">
@endsection

@section('content')
    <div class="art-page">

        <div class="art-title-row">
            <div class="art-title-bar"></div>
            <h1 class="art-title">申請一覧</h1>
        </div>

        {{-- タブ（URL変えずJSで切替） --}}
        <div class="scr-tab-wrap">
            <button type="button" class="scr-tab is-active" data-tab="pending">承認待ち</button>
            <button type="button" class="scr-tab" data-tab="approved">承認済み</button>
        </div>
        <div class="scr-tab-line"></div>

        {{-- 一覧枠 --}}
        <div class="art-tablewrap">
            <table class="art-table">
                <thead>
                    <tr class="art-head">
                        <th class="th-status">状態</th>
                        <th class="th-name">名前</th>
                        <th class="th--target-datetime">対象日時</th>
                        <th class="th-reason">申請理由</th>
                        <th class="th-request-at">申請日時</th>
                        <th class="th-detail">詳細</th>
                    </tr>
                </thead>

                {{-- 承認待ち --}}
                <tbody id="tab-pending">
                    @forelse($pendingRequests as $req)
                        <tr>
                            <td class="col-status">承認待ち</td>
                            <td class="col-name">{{ Auth::user()->name ?? '' }}</td>
                            <td class="col-target-datetime">
                                {{ $req->date }}
                                <br>
                                {{ $req->clock_in ?? '' }}
                            </td>
                            <td class="col-reason">{{ $req->note ?? '' }}</td>
                            <td class="col-request-at">{{ $req->created_at?->format('Y-m-d') }}</td>
                            <td class="col-detail">
                                <a href="{{ url('/attendance/detail/' . $req->attendance_id) }}"
                                    class="art-detaillink">詳細</a>
                            </td>
                        </tr>
                    @empty
                    @endforelse
                </tbody>

                {{-- 承認済み --}}
                <tbody id="tab-approved" style="display:none;">
                    @forelse($approvedRequests as $req)
                        <tr>
                            <td class="col-status">承認済み</td>
                            <td class="col-name">{{ Auth::user()->name ?? '' }}</td>
                            <td class="col-target-datetime">
                                {{ $req->date }}
                                <br>
                                {{ $req->clock_in ?? '' }}
                            </td>
                            <td class="col-reason">{{ $req->note ?? '' }}</td>
                            <td class="col-request-at">{{ $req->created_at?->format('Y-m-d') }}</td>
                            <td class="col-detail">
                                <a href="{{ url('/attendance/detail/' . $req->attendance_id) }}"
                                    class="art-detaillink">詳細</a>
                            </td>
                        </tr>
                    @empty
                    @endforelse
                </tbody>


            </table>
        </div>

    </div>

    {{-- JS：タブ切替（URL変えない） --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabs = document.querySelectorAll('.scr-tab[data-tab]');
            const pendingBody = document.getElementById('tab-pending');
            const approvedBody = document.getElementById('tab-approved');

            function setTab(key) {
                pendingBody.style.display = (key === 'pending') ? '' : 'none';
                approvedBody.style.display = (key === 'approved') ? '' : 'none';
                tabs.forEach(btn => btn.classList.toggle('is-active', btn.dataset.tab === key));
            }

            tabs.forEach(btn => {
                btn.addEventListener('click', () => setTab(btn.dataset.tab));
            });

            // 初期は常に承認待ち
            setTab('pending');
        });
    </script>
@endsection
