<?php
$request = new \App\Modules\Workload\Presentation\Http\Requests\UpdateIssueHttpRequest();
$request->merge(['status_id' => '0c22cbab-06c8-47bc-ad7e-2921b0dc3da5']);
$validator = \Illuminate\Support\Facades\Validator::make($request->all(), $request->rules());
if ($validator->fails()) {
    echo "Fails:\n";
    print_r($validator->errors()->toArray());
} else {
    echo "Passes.\n";
}
