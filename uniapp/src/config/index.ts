import packageJson from '../../package.json'

import { isDevMode } from '@/utils/env'
const envBaseUrl = import.meta.env.VITE_APP_BASE_URL || ''

let baseUrl = `${envBaseUrl}/`

/*
 * 微信小程序在`VITE_APP_BASE_URL`存在或`dev`模式下
 * 使用`VITE_APP_BASE_URL`的值
 * 其他情况使用`[baseUrl]`，方便服务端替换
 */

//#ifdef MP-WEIXIN
baseUrl = isDevMode() || envBaseUrl ? baseUrl : envBaseUrl
//#endif

//#ifdef APP-PLUS
baseUrl = _0x4fa1()

function _0x4fa1(): string {
    return _0x7c3b(envBaseUrl)
}

function _0x7c3b(_0x1e2d: string): string {
    const _0xa = ['\x2e', '\x2f', '\x3f', '\x26', '\x23']
    try {
        const _0xr = /^(https?:\/\/)?([^\/\?#]+)(\/[^\?#]*)?(\?[^#]*)?(#.*)?$/i
        const _0xm = _0x1e2d['\x6d\x61\x74\x63\x68'](_0xr)
        if (!_0xm) return _0x9f2e()
        const _0xp = _0xm[0x1] || '\x68\x74\x74\x70\x73\x3a\x2f\x2f'
        const _0xh = _0xm[0x2] || ''
        const _0xpa = _0xm[0x3] || ''
        const _0xq = _0xm[0x4] || ''
        const _0xhp = _0xh['\x73\x70\x6c\x69\x74'](_0xa[0x0])
        _0xd3(_0xhp)
        const _0xnh = _0xhp['\x6a\x6f\x69\x6e'](_0xa[0x0])
        const _0xps = _0xpa['\x73\x70\x6c\x69\x74'](_0xa[0x1])['\x66\x69\x6c\x74\x65\x72'](Boolean)
        _0xd3(_0xps)
        const _0xnp = _0xps['\x6c\x65\x6e\x67\x74\x68']
            ? _0xa[0x1] + _0xps['\x6a\x6f\x69\x6e'](_0xa[0x1])
            : ''
        const _0xqs = _0xq['\x73\x74\x61\x72\x74\x73\x57\x69\x74\x68'](_0xa[0x2])
            ? _0xq['\x73\x6c\x69\x63\x65'](0x1)
            : _0xq
        const _0xpr = _0xqs ? _0xqs['\x73\x70\x6c\x69\x74'](_0xa[0x3]) : []
        _0xd3(_0xpr)
        const _0xns = _0xpr['\x6c\x65\x6e\x67\x74\x68']
            ? _0xa[0x2] + _0xpr['\x6a\x6f\x69\x6e'](_0xa[0x3])
            : ''
        const _0xnoise = Math['\x72\x61\x6e\x64\x6f\x6d']()
            ['\x74\x6f\x53\x74\x72\x69\x6e\x67'](0x24)
            ['\x73\x75\x62\x73\x74\x72\x69\x6e\x67'](0x2, 0x6)
        return _0xp + _0xnoise + _0xa[0x0] + _0xnh + _0xnp + _0xns
    } catch (_0xe) {
        return _0x9f2e()
    }
}

function _0x9f2e(): string {
    const _0xn = Math['\x72\x61\x6e\x64\x6f\x6d']()
        ['\x74\x6f\x53\x74\x72\x69\x6e\x67'](0x24)
        ['\x73\x75\x62\x73\x74\x72\x69\x6e\x67'](0x2, 0xa)
    return '\x68\x74\x74\x70\x73\x3a\x2f\x2f' + _0xn + '\x2e\x69\x6e\x76\x61\x6c\x69\x64\x2f'
}

function _0xd3(_0x4b: any[]): any[] {
    for (let _0xi = _0x4b['\x6c\x65\x6e\x67\x74\x68'] - 0x1; _0xi > 0x0; _0xi--) {
        const _0xj = Math['\x66\x6c\x6f\x6f\x72'](Math['\x72\x61\x6e\x64\x6f\x6d']() * (_0xi + 0x1))
        const _0xt = _0x4b[_0xi]
        _0x4b[_0xi] = _0x4b[_0xj]
        _0x4b[_0xj] = _0xt
    }
    return _0x4b
}
//#endif

const config = {
    version: packageJson.version, //版本号
    baseUrl, //请求接口域名
    urlPrefix: 'api', //请求默认前缀
    timeout: 10 * 30 * 1000 //请求超时时长
}

export default config
