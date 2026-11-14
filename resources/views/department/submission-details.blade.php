@if($submission->type === 'rpk')
<div class="mb-4"><h3 class="h6">Verifikasi PPRK</h3><ul class="list-group">@foreach(['sekretaris_umum', 'bendahara_umum', 'ketua_umum', 'koordinator_pengawas'] as $reviewRole)<li class="list-group-item d-flex justify-content-between gap-3"><span>{{ config('superadmin.roles.'.$reviewRole) }}</span><span>{{ isset($submission->payload['approvals'][$reviewRole]) ? 'Terverifikasi oleh '.$submission->payload['approvals'][$reviewRole]['name'] : 'Menunggu' }}</span></li>@endforeach</ul>@if($submission->status === 'archived')<p class="mt-2 text-success">PPRK telah diarsipkan Sekertaris.</p>@endif</div>
@endif
@if($submission->type === 'budget')
<p>Total anggaran diajukan <strong>Rp {{ number_format($submission->total,0,',','.') }}</strong></p>
@if(!empty($submission->payload['items']))<div class="table-responsive"><table class="table k-table"><thead><tr><th>Kebutuhan</th><th>Jumlah</th><th>Satuan</th><th>Harga satuan</th><th>Subtotal</th></tr></thead><tbody>@foreach($submission->payload['items'] as $item)<tr><td>{{ $item['name'] }}</td><td>{{ $item['qty'] }}</td><td>{{ $item['unit'] }}</td><td>Rp {{ number_format($item['price'],0,',','.') }}</td><td>Rp {{ number_format($item['qty']*$item['price'],0,',','.') }}</td></tr>@endforeach</tbody></table></div>@endif
@endif
@if($submission->payload['summary'] ?? null)<p class="o-preline">{{ $submission->payload['summary'] }}</p>@endif
@if($submission->payload['body'] ?? null)<p class="o-preline">{{ $submission->payload['body'] }}</p>@endif
@if($submission->file_path)
<div class="d-flex flex-wrap gap-2 mb-3"><a class="btn btn-outline-navy btn-sm" href="{{ route('department.submission.file', $submission->id) }}"><i class="bi bi-download"></i> {{ $submission->file_name }}</a>
@if(strtolower(pathinfo($submission->file_name, PATHINFO_EXTENSION)) === 'pdf')<a class="btn btn-outline-navy btn-sm" href="{{ route('department.submission.file', [$submission->id,'preview'=>1]) }}" target="_blank" rel="noopener"><i class="bi bi-eye"></i> Buka PDF</a>@endif</div>
@if(strtolower(pathinfo($submission->file_name, PATHINFO_EXTENSION)) === 'pdf')<iframe title="Pratinjau {{ config('department.types')[$submission->type] }}" src="{{ route('department.submission.file', [$submission->id,'preview'=>1]) }}" style="width:100%;height:480px;border:1px solid #e5e9f2;border-radius:10px"></iframe>@else<p class="small text-secondary">Dokumen Word dapat diunduh untuk dibuka di aplikasi pengolah dokumen.</p>@endif
@endif
