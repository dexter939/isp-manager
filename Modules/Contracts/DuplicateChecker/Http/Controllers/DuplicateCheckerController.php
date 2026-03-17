<?php

namespace Modules\Contracts\DuplicateChecker\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Modules\Contracts\DuplicateChecker\Http\Requests\CheckDuplicateRequest;
use Modules\Contracts\DuplicateChecker\Services\CustomerDuplicateChecker;

class DuplicateCheckerController extends ApiController
{
    public function __construct(private CustomerDuplicateChecker $checker) {}

    public function check(CheckDuplicateRequest $request): JsonResponse
    {
        $validated  = $request->validated();
        $excludeId  = $request->input('exclude_id');
        $duplicates = [];
        if ($email = $request->input('email')) {
            $duplicates = array_merge($duplicates, $this->checker->checkEmail($email, $excludeId));
        }
        if ($phone = $request->input('phone')) {
            $phoneDuplicates = $this->checker->checkPhone($phone, $excludeId);
            foreach ($phoneDuplicates as $dup) {
                if (!in_array($dup->id, array_column($duplicates, 'id'))) {
                    $duplicates[] = $dup;
                }
            }
        }
        return response()->json(['duplicates' => $duplicates, 'has_duplicates' => count($duplicates) > 0]);
    }
}
