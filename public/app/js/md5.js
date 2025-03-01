function rhex(i) {
    for (str = "", j = 0; j <= 3; j++) str += hex_chr.charAt(i >> 8 * j + 4 & 15) + hex_chr.charAt(i >> 8 * j & 15);
    return str;
}

function str2blks_MD5(x) {
    for (nblk = (x.length + 8 >> 6) + 1, blks = new Array(16 * nblk), i = 0; i < 16 * nblk; i++) blks[i] = 0;
    for (i = 0; i < x.length; i++) blks[i >> 2] |= x.charCodeAt(i) << i % 4 * 8;
    return blks[i >> 2] |= 128 << i % 4 * 8, blks[16 * nblk - 2] = 8 * x.length, blks
}

function add(i, x) {
    var h = (65535 & i) + (65535 & x), f = (i >> 16) + (x >> 16) + (h >> 16);
    return f << 16 | 65535 & h
}

function rol(i, x) {
    return i << x | i >>> 32 - x
}

function cmn(i, x, h, f, r, n) {
    return add(rol(add(add(x, i), add(f, n)), r), h)
}

function ff(i, x, h, f, r, n, g) {
    return cmn(x & h | ~x & f, i, x, r, n, g)
}

function gg(i, x, h, f, r, n, g) {
    return cmn(x & f | h & ~f, i, x, r, n, g)
}

function hh(i, x, h, f, r, n, g) {
    return cmn(x ^ h ^ f, i, x, r, n, g)
}

function ii(i, x, h, f, r, n, g) {
    return cmn(h ^ (x | ~f), i, x, r, n, g)
}

function MD5(h) {
    x = str2blks_MD5(h);
    var f = 1732584193, r = -271733879, n = -1732584194, g = 271733878;
    for (i = 0; i < x.length; i += 16) {
        var t = f, e = r, c = n, d = g;
        f = ff(f, r, n, g, x[i + 0], 7, -680876936), g = ff(g, f, r, n, x[i + 1], 12, -389564586), n = ff(n, g, f, r, x[i + 2], 17, 606105819), r = ff(r, n, g, f, x[i + 3], 22, -1044525330), f = ff(f, r, n, g, x[i + 4], 7, -176418897), g = ff(g, f, r, n, x[i + 5], 12, 1200080426), n = ff(n, g, f, r, x[i + 6], 17, -1473231341), r = ff(r, n, g, f, x[i + 7], 22, -45705983), f = ff(f, r, n, g, x[i + 8], 7, 1770035416), g = ff(g, f, r, n, x[i + 9], 12, -1958414417), n = ff(n, g, f, r, x[i + 10], 17, -42063), r = ff(r, n, g, f, x[i + 11], 22, -1990404162), f = ff(f, r, n, g, x[i + 12], 7, 1804603682), g = ff(g, f, r, n, x[i + 13], 12, -40341101), n = ff(n, g, f, r, x[i + 14], 17, -1502002290), r = ff(r, n, g, f, x[i + 15], 22, 1236535329), f = gg(f, r, n, g, x[i + 1], 5, -165796510), g = gg(g, f, r, n, x[i + 6], 9, -1069501632), n = gg(n, g, f, r, x[i + 11], 14, 643717713), r = gg(r, n, g, f, x[i + 0], 20, -373897302), f = gg(f, r, n, g, x[i + 5], 5, -701558691), g = gg(g, f, r, n, x[i + 10], 9, 38016083), n = gg(n, g, f, r, x[i + 15], 14, -660478335), r = gg(r, n, g, f, x[i + 4], 20, -405537848), f = gg(f, r, n, g, x[i + 9], 5, 568446438), g = gg(g, f, r, n, x[i + 14], 9, -1019803690), n = gg(n, g, f, r, x[i + 3], 14, -187363961), r = gg(r, n, g, f, x[i + 8], 20, 1163531501), f = gg(f, r, n, g, x[i + 13], 5, -1444681467), g = gg(g, f, r, n, x[i + 2], 9, -51403784), n = gg(n, g, f, r, x[i + 7], 14, 1735328473), r = gg(r, n, g, f, x[i + 12], 20, -1926607734), f = hh(f, r, n, g, x[i + 5], 4, -378558), g = hh(g, f, r, n, x[i + 8], 11, -2022574463), n = hh(n, g, f, r, x[i + 11], 16, 1839030562), r = hh(r, n, g, f, x[i + 14], 23, -35309556), f = hh(f, r, n, g, x[i + 1], 4, -1530992060), g = hh(g, f, r, n, x[i + 4], 11, 1272893353), n = hh(n, g, f, r, x[i + 7], 16, -155497632), r = hh(r, n, g, f, x[i + 10], 23, -1094730640), f = hh(f, r, n, g, x[i + 13], 4, 681279174), g = hh(g, f, r, n, x[i + 0], 11, -358537222), n = hh(n, g, f, r, x[i + 3], 16, -722521979), r = hh(r, n, g, f, x[i + 6], 23, 76029189), f = hh(f, r, n, g, x[i + 9], 4, -640364487), g = hh(g, f, r, n, x[i + 12], 11, -421815835), n = hh(n, g, f, r, x[i + 15], 16, 530742520), r = hh(r, n, g, f, x[i + 2], 23, -995338651), f = ii(f, r, n, g, x[i + 0], 6, -198630844), g = ii(g, f, r, n, x[i + 7], 10, 1126891415), n = ii(n, g, f, r, x[i + 14], 15, -1416354905), r = ii(r, n, g, f, x[i + 5], 21, -57434055), f = ii(f, r, n, g, x[i + 12], 6, 1700485571), g = ii(g, f, r, n, x[i + 3], 10, -1894986606), n = ii(n, g, f, r, x[i + 10], 15, -1051523), r = ii(r, n, g, f, x[i + 1], 21, -2054922799), f = ii(f, r, n, g, x[i + 8], 6, 1873313359), g = ii(g, f, r, n, x[i + 15], 10, -30611744), n = ii(n, g, f, r, x[i + 6], 15, -1560198380), r = ii(r, n, g, f, x[i + 13], 21, 1309151649), f = ii(f, r, n, g, x[i + 4], 6, -145523070), g = ii(g, f, r, n, x[i + 11], 10, -1120210379), n = ii(n, g, f, r, x[i + 2], 15, 718787259), r = ii(r, n, g, f, x[i + 9], 21, -343485551), f = add(f, t), r = add(r, e), n = add(n, c), g = add(g, d)
    }
    return rhex(f) + rhex(r) + rhex(n) + rhex(g)
}

var hex_chr = "0123456789abcdef";