<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../backend/lib/access.php';
$assertions = 0;
function accessExpect($actual, $expected, string $message): void {
    global $assertions;
    if ($actual !== $expected) throw new RuntimeException($message);
    $assertions++;
}
$legacy = ['id' => '6', 'email' => 'trabajador@example.invalid', 'role' => ''];
$worker = appUserPayload($legacy);
accessExpect($worker['role'], 'cashier', 'Legacy empty role must remain a worker.');
accessExpect($worker['name'], 'trabajador', 'Legacy name falls back to email local part.');
accessExpect($worker['permissions'], ['sales','inventory'], 'Worker defaults are products/sales and inventory.');
accessExpect(appUserPayload($legacy + ['permissions_json'=>null])['permissions'], ['sales','inventory'], 'NULL preserves defaults.');
accessExpect(appUserPayload($legacy + ['permissions_json'=>'[]'])['permissions'], [], 'Empty list explicitly revokes all sections.');
accessExpect(appUserPayload($legacy + ['permissions_json'=>'not-json'])['permissions'], [], 'Malformed JSON fails closed.');
accessExpect(appUserPayload($legacy + ['permissions_json'=>'{"sales":true}'])['permissions'], [], 'Permission maps do not grant unexpected access.');
accessExpect(appUserPayload($legacy + ['permissions_json'=>'["sales","users","dashboard",["inventory"]]'])['permissions'], ['dashboard','sales'], 'Unknown/nested keys cannot grant access.');
$admin = appUserPayload(['id'=>'5','name'=>'Administración','email'=>'admin@example.invalid','role'=>'admin','permissions_json'=>'[]','password'=>'secret']);
accessExpect($admin['permissions'], appPermissionKeys(), 'Administrators retain all section access.');
accessExpect(array_key_exists('password',$admin), false, 'Public payload never includes password hashes.');
accessExpect(appHasPermission($worker,'sales'), true, 'Worker can use default sales module.');
accessExpect(appHasPermission($worker,'dashboard'), false, 'Default worker cannot enter dashboard.');
accessExpect(appHasPermission($worker,['customers','inventory']), true, 'Read dependencies can allow any explicitly permitted section.');
accessExpect(appHasPermission($worker,'sales',true), false, 'Section access never grants user administration.');
accessExpect(appHasPermission($admin,[],true), true, 'Administrator can manage personnel.');
accessExpect(appHasPermission(null,'sales'), false, 'Unauthenticated access is denied.');
echo "User access: $assertions normalization, defaults, deny-all and administrator assertions passed.\n";
