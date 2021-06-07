
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
