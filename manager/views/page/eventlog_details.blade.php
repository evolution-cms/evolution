@extends('manager::template.page')
@section('content')
    @push('scripts.top')
        <script type="text/javascript">
            var actions = {
                delete: function() {
                    if(confirm("{{ ManagerTheme::getLexicon('confirm_delete_eventlog') }}") === true) {
                        document.location.href = "index.php?id=" + document.resource.id.value + "&a=116";
                    }
                },
                cancel: function() {
                    documentDirty = false;
                    document.location.href = 'index.php?a=114';
                }
            };
        </script>
    @endpush

    <h1>
        {{ ManagerTheme::getLexicon('eventlog') }}
    </h1>

    {{ ManagerTheme::styleHtml('actionbuttons.dynamic.canceldelete') }}

    <?php /** @var EvolutionCMS\Models\EventLog $log */?>
    @if($log->exists)
        <form name="resource" method="get">
            <input type="hidden" name="id" value="{{ $log->getKey() }}" />
            <input type="hidden" name="a" value="{{ $controller->getIndex() }}" />
            <input type="hidden" name="listmode" value="{{ get_by_key($_REQUEST, 'listmode') }}" />
            <input type="hidden" name="op" value="" />
            <div class="tab-page">
                <div class="container container-body">
                    @switch($log->type)
                        @case(EvolutionCMS\Models\EventLog::TYPE_INFORMATION)
                            <p><i class="{{$_style['icon_info_circle']}} text-info"></i> {{ManagerTheme::getLexicon('information')}}</p>
                            @break
                        @case(EvolutionCMS\Models\EventLog::TYPE_WARNING)
                            <p><i class="{{$_style['icon_info_triangle']}} text-warning"></i> {{ManagerTheme::getLexicon('warning')}}</p>
                            @break
                        @case(EvolutionCMS\Models\EventLog::TYPE_ERROR)
                            <p><i class="{{$_style['icon_ban']}} text-danger"></i> {{ManagerTheme::getLexicon('error')}}</p>
                            @break
                        @case(EvolutionCMS\Models\EventLog::TYPE_MAIL_SENT)
                            <p><i class="{{$_style['icon_mail']}} text-success"></i> {{ManagerTheme::getLexicon('eventlog_mail_sent')}}</p>
                            @break
                        @default:
                            <p>N/A</p>
                    @endswitch

                    <p><b>{{ $log->source }} - {{ ManagerTheme::getLexicon('eventlog_viewer') }}</b></p>

                    <table class="table">
                        <tr>
                            <td width="25%" valign="top">{{ ManagerTheme::getLexicon('event_id') }}:</td>
                            <td width="25%" valign="top">{{ $log->eventid }}</td>
                            <td width="25%" valign="top">{{ ManagerTheme::getLexicon('source') }}:</td>
                            <td width="25%" valign="top">{{ $log->source }}</td>
                        </tr>
                        <tr>
                            <td width="25%" valign="top">{{ ManagerTheme::getLexicon('date') }}:</td>
                            <td width="25%" valign="top">{{ EvolutionCMS\Models\EventLog::formatStoredTimestamp($log->getRawOriginal('createdon'), EvolutionCMS()->getConfig('site_timezone'), 'Y-m-d H:i:s') }}</td>
                            <td width="25%" valign="top">{{ ManagerTheme::getLexicon('user') }}:</td>
                            <td width="25%" valign="top">{{ $log->getUser() !== null ? $log->getUser()->username : ManagerTheme::getLexicon('eventlog_system_user') }}</td>
                        </tr>
                        <tr>
                            <td width="100%" colspan="4"><br />
                                {{ $log->descriptionHtml() }}
                                @if($log->isMailSentType() && $log->mailBody() !== null)
                                    <hr />
                                    <iframe
                                        sandbox=""
                                        referrerpolicy="no-referrer"
                                        srcdoc="{{ $log->mailBody() }}"
                                        style="width:100%;height:600px;border:1px solid #dfe7ec;background:#fff;"
                                        title="{{ ManagerTheme::getLexicon('eventlog_mail_sent') }}"
                                    ></iframe>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </form>
    @endif
@endsection
