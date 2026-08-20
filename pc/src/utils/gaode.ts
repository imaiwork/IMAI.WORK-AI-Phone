/**
 * 高德 POI 搜索封装(浏览器直连,CORS allow-origin: *)
 *
 * 业务流:
 *   1. Coze 工作流把自然语言翻译成 {biz, city, region, location_extra, target_count}
 *   2. 这里负责:把这些参数喂高德 API,翻页拉到目标条数,过滤无电话商家
 *
 * 端点:
 *   - V5 文本搜索:/v5/place/text(主力,有 region + keywords 时用)
 *   - V5 周边搜索:/v5/place/around(有 location_extra 时:先 geocode 再 around)
 *   - V3 地理编码:/v3/geocode/geo(地址 → 经纬度,给 around 用)
 */

const KEY = "38482ff0895c4de5a7b6c6adc0974223";

const TEXT_URL = "https://restapi.amap.com/v5/place/text";
const AROUND_URL = "https://restapi.amap.com/v5/place/around";
const GEOCODE_URL = "https://restapi.amap.com/v3/geocode/geo";

// 高德原始 POI(只列我们用到的字段)
interface GaodePoi {
    id: string;
    name: string;
    address?: string | string[];
    location?: string; // "lng,lat"
    pname?: string;
    cityname?: string;
    adname?: string;
    type?: string;
    typecode?: string;
    business?: {
        tel?: string | string[];
        rating?: string;
        opentime?: string | string[];
    };
}

// 规整后用于 UI 渲染的卡片
export interface PoiCard {
    key: string;       // gaode id
    name: string;
    addr: string;
    phone: string;
    tag: string;       // 业态类型(用于 biz-tag 蓝标签)
    rating: string;    // 评分(显示用)
    location: string;  // 经纬度
}

interface GeocodeResult {
    ok: boolean;
    location: string;
    level: string;
    formatted: string;
}

async function get<T = any>(url: string, params: Record<string, string | number>): Promise<T> {
    params.key = KEY as any;
    const qs = Object.entries(params)
        .filter(([, v]) => v !== undefined && v !== null && v !== "")
        .map(([k, v]) => `${k}=${encodeURIComponent(String(v))}`)
        .join("&");
    const resp = await fetch(`${url}?${qs}`);
    if (!resp.ok) throw new Error(`Gaode HTTP ${resp.status}`);
    const j = await resp.json();
    if (j.status !== "1") {
        throw new Error(`Gaode 错误: ${j.info || j.infocode}`);
    }
    return j;
}

/** 地理编码(地址 → 经纬度) */
export async function gaodeGeocode(address: string, city = ""): Promise<GeocodeResult> {
    try {
        const j = await get(GEOCODE_URL, { address, city });
        const g = (j.geocodes && j.geocodes[0]) || null;
        if (!g) return { ok: false, location: "", level: "", formatted: "" };
        return {
            ok: true,
            location: g.location || "",
            level: g.level || "",
            formatted: g.formatted_address || "",
        };
    } catch {
        return { ok: false, location: "", level: "", formatted: "" };
    }
}

// 文本/周边搜索返回:pois + count(高德报告的总匹配数,用于显示"X/Y")
interface SearchResult {
    pois: GaodePoi[];
    count: number;
}

/** 文本搜索(主力)— 翻页用 pageNum,每页 25 */
export async function gaodeTextSearch(args: {
    keywords: string;
    region?: string;
    types?: string;
    pageSize?: number;
    pageNum?: number;
}): Promise<SearchResult> {
    const j = await get(TEXT_URL, {
        keywords: args.keywords,
        region: args.region || "",
        city_limit: args.region ? "true" : "false",
        types: args.types || "",
        page_size: args.pageSize ?? 25,
        page_num: args.pageNum ?? 1,
        show_fields: "business",
    });
    return {
        pois: (j.pois ?? []) as GaodePoi[],
        count: parseInt(j.count ?? "0", 10) || 0,
    };
}

/** 周边搜索(有具体地名/坐标时用) */
export async function gaodeAroundSearch(args: {
    location: string;
    keywords: string;
    radius?: number;
    types?: string;
    pageSize?: number;
    pageNum?: number;
}): Promise<SearchResult> {
    const j = await get(AROUND_URL, {
        location: args.location,
        keywords: args.keywords,
        radius: args.radius ?? 3000,
        types: args.types || "",
        page_size: args.pageSize ?? 25,
        page_num: args.pageNum ?? 1,
        show_fields: "business",
    });
    return {
        pois: (j.pois ?? []) as GaodePoi[],
        count: parseInt(j.count ?? "0", 10) || 0,
    };
}

/** 根据 geocode 的 level 自动决定 around 搜索半径(米) */
export function radiusForLevel(level: string): number {
    const map: Record<string, number> = {
        门牌号: 1000,
        兴趣点: 1500,
        道路: 2000,
        道路交叉路口: 2000,
        热点商圈: 2500,
        村庄: 3000,
        乡镇: 3500,
        开发区: 4000,
        区县: 5000,
        市: 10000,
    };
    return map[level] ?? 3000;
}

/** 过滤有电话的(获客只关心能联系上的) */
export function filterPoisWithTel(pois: GaodePoi[]): GaodePoi[] {
    return pois.filter((p) => {
        const t = p.business?.tel;
        if (!t) return false;
        if (Array.isArray(t)) return t.length > 0 && t.some(Boolean);
        return String(t).trim().length > 0;
    });
}

/** 把高德 POI 规整成 UI 用的卡片结构 */
export function extractPoiCard(p: GaodePoi): PoiCard {
    let tel: string = "";
    const rawTel = p.business?.tel;
    if (Array.isArray(rawTel)) tel = rawTel.filter(Boolean).join(" / ");
    else if (rawTel) tel = String(rawTel);

    const addr = Array.isArray(p.address) ? p.address.join("") : (p.address ?? "");

    return {
        key: p.id || `${p.name}-${p.location}`,
        name: p.name || "",
        addr: addr,
        phone: tel,
        tag: p.type ? p.type.split(";")[0] : "本地服务",
        rating: p.business?.rating || "",
        location: p.location || "",
    };
}

/**
 * 给文本搜索拼装最稳的 region + keywords
 *
 * 高德 V5 API 大坑:region 只填"南山区"会全国匹配(鹤岗市也有南山区,大量同名)
 * 经验证最稳的写法:
 *   - region 用市级("深圳市")
 *   - 把"区/街道"塞进 keywords 前缀
 *
 * 例:city=深圳 region=南山区 biz=美容店
 *   → region="深圳市", keywords="南山 美容店"
 */
function composeTextSearchParams(p: {
    biz: string;
    city: string;
    region: string;
    location_extra: string;
}): { keywords: string; region: string } {
    const parts: string[] = [];
    // 把行政区/细分地名塞进 keywords 前缀
    if (p.region) parts.push(p.region.replace(/区$|市$|县$/g, ""));
    if (p.location_extra) parts.push(p.location_extra);
    parts.push(p.biz);
    const keywords = parts.filter(Boolean).join(" ");

    // region 永远用市级,不传区
    let region = p.city || p.region;
    if (region && !region.endsWith("市") && !region.endsWith("省")) {
        // 区级 → 兜底:直接传(没 city 的情况)
        if (!p.city) region = p.region;
    }
    return { keywords, region };
}

/**
 * 按目标条数翻页拉取(自动调 text 或 around),返回过滤+规整后的卡片
 * 注意:翻页基于 page_num,从外部维护 pageNum 状态
 * @param args.fromPage 从第几页开始拉
 * @param args.batchPages 这次拉几页
 * @return { cards, nextPage, exhausted }
 */
export async function fetchPoisBatch(args: {
    biz: string;
    region: string;
    location_extra: string;
    city: string;
    types: string;
    fromPage: number;
    batchPages?: number;
}): Promise<{ cards: PoiCard[]; nextPage: number; exhausted: boolean; totalCount: number }> {
    const batchPages = args.batchPages ?? 1;
    const cards: PoiCard[] = [];
    let exhausted = false;
    let useAround = false;
    let aroundLocation = "";
    let aroundRadius = 3000;
    let totalCount = 0;

    if (args.location_extra) {
        const addr = [args.region, args.location_extra].filter(Boolean).join("");
        const geo = await gaodeGeocode(addr, args.city);
        if (geo.ok) {
            useAround = true;
            aroundLocation = geo.location;
            aroundRadius = radiusForLevel(geo.level);
        }
    }

    const text = composeTextSearchParams(args);

    let page = args.fromPage;
    for (let i = 0; i < batchPages; i++) {
        const result = useAround
            ? await gaodeAroundSearch({
                  location: aroundLocation,
                  keywords: args.biz,
                  radius: aroundRadius,
                  types: args.types,
                  pageSize: 25,
                  pageNum: page,
              })
            : await gaodeTextSearch({
                  keywords: text.keywords,
                  region: text.region,
                  types: args.types,
                  pageSize: 25,
                  pageNum: page,
              });

        if (i === 0) totalCount = result.count;

        const valid = filterPoisWithTel(result.pois).map(extractPoiCard);
        cards.push(...valid);

        if (result.pois.length < 25) {
            exhausted = true;
            page += 1;
            break;
        }
        page += 1;
    }

    return { cards, nextPage: page, exhausted, totalCount };
}
