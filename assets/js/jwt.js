// From: https://jwt.io/js/jwt.js

;(function () {

  var object =
    typeof exports != 'undefined' ? exports :
    typeof self != 'undefined' ? self : // #8: web workers
    $.global; // #31: ExtendScript

  var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';

  function InvalidCharacterError(message) {
    this.message = message;
  }
  InvalidCharacterError.prototype = new Error;
  InvalidCharacterError.prototype.name = 'InvalidCharacterError';

  // encoder
  // [https://gist.github.com/999166] by [https://github.com/nignag]
  object.btoa || (
  object.btoa = function (input) {
    var str = String(input);
    for (
      // initialize result and counter
      var block, charCode, idx = 0, map = chars, output = '';
      // if the next str index does not exist:
      //   change the mapping table to "="
      //   check if d has no fractional digits
      str.charAt(idx | 0) || (map = '=', idx % 1);
      // "8 - idx % 1 * 8" generates the sequence 2, 4, 6, 8
      output += map.charAt(63 & block >> 8 - idx % 1 * 8)
    ) {
      charCode = str.charCodeAt(idx += 3/4);
      if (charCode > 0xFF) {
        throw new InvalidCharacterError("'btoa' failed: The string to be encoded contains characters outside of the Latin1 range.");
      }
      block = block << 8 | charCode;
    }
    return output;
  });

  // decoder
  // [https://gist.github.com/1020396] by [https://github.com/atk]
  (
  object.atob = function (input) {
    var str = String(input).replace(/[=]+$/, ''); // #31: ExtendScript bad parse of /=
    if (str.length % 4 == 1) {
      throw new InvalidCharacterError("'atob' failed: The string to be decoded is not correctly encoded.");
    }
    for (
      // initialize result and counters
      var bc = 0, bs, buffer, idx = 0, output = '';
      // get next character
      buffer = str.charAt(idx++);
      // character found in table? initialize bit storage and add its ascii value;
      ~buffer && (bs = bc % 4 ? bs * 64 + buffer : buffer,
        // and if not first of each 4 characters,
        // convert the first 8 bits to one ascii character
        bc++ % 4) ? output += String.fromCharCode(255 & bs >> (-2 * bc & 6)) : 0
    ) {
      // try to find character in table (0-63, not found => -1)
      buffer = chars.indexOf(buffer);
    }
    return output;
  });

}());


// XXX Hack to prevent hextorstr function used by JWS send a string instead of
// a Word Array. On this way, no string decoding needs to take place and Crypto
// takes care of everything.
// Note that it should not affect the other algorithms as hextorstr is exclusively
// used on Hmac family (that invokes CryptoJS library).
window.hextorstr = function (c) {
  return window.CryptoJS.enc.Hex.parse(c);
};


//this is used to parse base64
function url_base64_decode(str) {
  var output = str.replace(/-/g, '+').replace(/_/g, '/');
  switch (output.length % 4) {
    case 0:
      break;
    case 2:
      output += '==';
      break;
    case 3:
      output += '=';
      break;
    default:
      throw 'Illegal base64url string!';
  }
  var result = window.atob(output); //polifyll https://github.com/davidchambers/Base64.js
  try{
    return decodeURIComponent(escape(result));
  } catch (err) {
    return result;
  }
}

window.decode = function (base64json) {
  var json = null, error = null;
  try {
    json = url_base64_decode(base64json);
    json = JSON.stringify(JSON.parse(json), undefined, 2);
  } catch (e) {
    error = e;
  }
  return {result: json, error: error};
};

window.sign = function (algorithm, header, payload, key, isSecretBase64Encoded) {
  var value = '', error = null, headerAsJSON, payloadAsJSON;

  try {
    headerAsJSON = JSON.stringify(JSON.parse(header));
  } catch (e) {
    error = {result: null, error: {cause: e, who: ['header']}};
  }
  try {
    payloadAsJSON = JSON.stringify(JSON.parse(payload));
  } catch (e) {
    if (error) {
      error.error.who.push('payload');
    } else {
      error = {result: null, error: {cause: e, who: ['payload']}};
    }
  }

  if (error) {
    return error;
  }

  if(algorithm === 'HS256'){
    if (isSecretBase64Encoded) {
      try {
        key = window.b64utob64(key);
        key = window.CryptoJS.enc.Base64.parse(key).toString();
      } catch (e) {
        return {result: '', error: e};
      }
    } else {
      key = window.CryptoJS.enc.Latin1.parse(key).toString();
    }
  }

  try {
    value = KJUR.jws.JWS.sign(algorithm, headerAsJSON, payloadAsJSON, key);
  } catch (e) {
    error = e;
  }

  return {result: value, error: error};
};

window.isValidBase64String = function (s) {
  try {
    s = window.b64utob64(s);
    window.CryptoJS.enc.Base64.parse(s).toString();
    return true;
  } catch (e) {
    return false;
  }
};

window.verify = function (algorithm, value, key, isSecretBase64Encoded) {

  var result = '', error = null;

  if (algorithm === 'HS256'){
    if (isSecretBase64Encoded) {
      try {
        key = window.b64utob64(key);
        key = window.CryptoJS.enc.Base64.parse(key).toString();
      } catch (e) {
        return {result: '', error: e};
      }
    } else {
      key = window.CryptoJS.enc.Latin1.parse(key).toString();
    }
  }

  try {
    result = KJUR.jws.JWS.verify(value, key);
  } catch (e) {
    error = e;
  }

  return {result: result, error: error};
};
