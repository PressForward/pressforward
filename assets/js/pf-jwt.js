<pre><?php
var_dump($pubkey = pressforward('controller.jwt')->get_a_user_public_key());
var_dump($privkey = pressforward('controller.jwt')->get_a_user_private_key_for_decrypt($pubkey));
$token = array( 'foo' => 'bar' );
var_dump($arg = pressforward('controller.jwt')->encode_with_jwt($token, $privkey));
//var_dump(pressforward('controller.jwt')->decode_with_jwt($arg, 'asdfasdfasdfas'));

window.sign('HS256', '{ "typ": "JWT", "alg": "HS256" }', '{ "foo": "bar" }', 'aHR0cGxvY2Fsd29yZHByZXNzZGV2ZDI4MTM4OWFmNzU4MGYyZDdmZTc1MGUxMDAxYzBmM2RlY2U1OTE0YmZkZWJjMWE5OGJiMDc4MjUwN2Y5NTk5MHBm', false );
var t = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJmb28iOiJiYXIifQ.EftYoAbOOcSVt0YkogEufgA_wrekVyFpTJ-wPExyj3A';
window.verify('HS256', t, 'aHR0cGxvY2Fsd29yZHByZXNzZGV2ZDI4MTM4OWFmNzU4MGYyZDdmZTc1MGUxMDAxYzBmM2RlY2U1OTE0YmZkZWJjMWE5OGJiMDc4MjUwN2Y5NTk5MHBm', false);
var parts = t.split('.');
window.decode(parts[1]);

var t = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJmb28iOiJiYXIifQ.EftYoAbOOcSVt0YkogEufgA_wrekVyFpTJ-wPExyj3A';
window.verify('HS256', t, 'aHR0cGxvY2Fsd29yZHByZXNzZGV2ZDI4MTM4OWFmNzU4MGYyZDdmZTc1MGUxMDAxYzBmM2RlY2U1OTE0YmZkZWJjMWE5OGJiMDc4MjUwN2Y5NTk5MHBm', false);
var parts = t.split('.');
window.decode(parts[1]);

window.vartesta = window.sign('HS256', '{ "typ": "JWT", "alg": "HS256" }', '{ "foo": "bar" }', 'aHR0cGxvY2Fsd29yZHByZXNzZGV2ZDI4MTM4OWFmNzU4MGYyZDdmZTc1MGUxMDAxYzBmM2RlY2U1OTE0YmZkZWJjMWE5OGJiMDc4MjUwN2Y5NTk5MHBm', false );
2<pre><?php
var_dump($pub = pressforward('controller.jwt')->get_a_user_public_key());
var_dump($priv = pressforward('controller.jwt')->get_a_user_private_key_for_decrypt($pub));
var_dump(pressforward('controller.jwt')->decode_with_jwt("eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJmb28iOiJiYXIifQ.EftYoAbOOcSVt0YkogEufgA_wrekVyFpTJ-wPExyj3A", $priv));
