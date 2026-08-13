<?php
/** Dependency-free OneID SSO response and identity regression suite. */
declare(strict_types=1);

require_once __DIR__ . '/../public/includes/sso-flow.php';

$passed = 0;
$failed = 0;

function ssoAssert(bool $condition, string $message): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        fwrite(STDOUT, "[PASS] {$message}\n");
        return;
    }
    $failed++;
    fwrite(STDERR, "[FAIL] {$message}\n");
}

$staff = SSO_BUILD_AUTH_HANDOFF(['data3' => '1234-56']);
ssoAssert($staff['identity_valid'] && $staff['resolved_source'] === 'data3', 'ID staf sah dipetakan daripada data3.');

$student = SSO_BUILD_AUTH_HANDOFF(['data4' => 'CA12345']);
ssoAssert($student['identity_valid'] && $student['resolved_source'] === 'data4', 'Matrik sah dipetakan daripada data4.');

$invalidIdentity = SSO_BUILD_AUTH_HANDOFF(['data3' => '123', 'data4' => 'A-123']);
ssoAssert(!$invalidIdentity['identity_valid'], 'Payload tanpa identifier sah ditolak.');

$ambiguous = SSO_BUILD_AUTH_HANDOFF(['data3' => '1234-56', 'data4' => 'CA12345']);
ssoAssert($ambiguous['identity_conflict'] && !$ambiguous['identity_valid'], 'Payload dengan dua identiti ditolak sebagai konflik.');

$valid = SSO_CLASSIFY_API_RESPONSE(['respond_flag' => '1', 'respond' => '1', 'respond_user_packet' => ['data3' => '1234-56']]);
ssoAssert($valid['status'] === 'valid' && empty($valid['reissued']), 'Respons token sah diterima.');

$invalidToken = SSO_CLASSIFY_API_RESPONSE(['respond_flag' => '1', 'respond' => '0']);
ssoAssert($invalidToken['status'] === 'invalid_token', 'Token ditolak dipetakan kepada invalid_token.');

$invalidSite = SSO_CLASSIFY_API_RESPONSE(['respond_flag' => '1', 'respond' => '0', 'message' => 'Invalid site id']);
ssoAssert($invalidSite['status'] === 'invalid_site', 'Ralat site ID dikenal pasti daripada respons OneID.');

$reissued = SSO_CLASSIFY_API_RESPONSE(['respond_flag' => '2', 'respond_new_token' => 'replacement', 'respond_user_packet' => ['data4' => 'CA12345']]);
ssoAssert($reissued['status'] === 'valid' && $reissued['reissued'] && $reissued['token'] === 'replacement', 'Auto-reissue menghasilkan token gantian yang sah.');

$brokenReissue = SSO_CLASSIFY_API_RESPONSE(['respond_flag' => '2']);
ssoAssert($brokenReissue['status'] === 'invalid_response', 'Auto-reissue tidak lengkap ditolak.');

ssoAssert(SSO_CLASSIFY_API_RESPONSE(null)['status'] === 'invalid_response', 'Respons kosong ditolak.');
ssoAssert(SSO_CLASSIFY_API_RESPONSE(['respond_flag' => '9'])['status'] === 'invalid_response', 'Flag tidak dikenali ditolak.');
ssoAssert(!SSO_VALIDATE_MATRIK('A-123') && !SSO_VALIDATE_STAFID('1234'), 'Format identifier yang salah ditolak.');

$clientSource = file_get_contents(__DIR__ . '/../public/sso_sp_client.php') ?: '';
ssoAssert(str_contains($clientSource, 'CURLOPT_CONNECTTIMEOUT, 5') && str_contains($clientSource, 'CURLOPT_TIMEOUT, 15'), 'Timeout sambungan OneID dilindungi.');
ssoAssert(str_contains($clientSource, 'CURLOPT_SSL_VERIFYHOST, 2') && str_contains($clientSource, 'CURLOPT_SSL_VERIFYPEER, true'), 'Pengesahan TLS OneID diaktifkan.');
ssoAssert(str_contains($clientSource, "function_exists('curl_init')"), 'Ketiadaan sambungan cURL dikendalikan sebagai kegagalan servis.');
ssoAssert(strpos($clientSource, "\$incomingToken =") < strpos($clientSource, "\$cookieToken ="), 'Token callback baharu diproses sebelum cookie lama.');
ssoAssert(!str_contains($clientSource, 'echo "X"'), 'Tiada lagi output X bagi auto-reissue.');

$loginSource = file_get_contents(__DIR__ . '/../public/login.php') ?: '';
ssoAssert(str_contains($loginSource, '$ssoHandoffTtlSeconds = 300'), 'Handoff SSO kekal mempunyai TTL lima minit.');
ssoAssert(str_contains($loginSource, "\$correlationId === ''"), 'Handoff tanpa correlation ID ditolak.');

fwrite(STDOUT, "\nResult: {$passed} passed, {$failed} failed.\n");
exit($failed === 0 ? 0 : 1);
