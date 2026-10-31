import request from "@/utils/request";

export function getPublicCopyBrowse(data: {
    persona_id: number | string;
    library_type?: number;
    driver_type?: number;
    page_no?: number;
    page_size?: number;
}) {
    return request.get({ url: "/copywriting.publicCopy/lists", data }, { ignoreCancel: true });
}

export function addPublicCopies(data: { persona_id: number | string; ids: number[] }) {
    return request.post({ url: "/copywriting.publicCopy/add", data });
}

export function refreshPublicCopy(data: { id: number; force?: number }) {
    return request.post({ url: "/copywriting.publicCopy/refresh", data }, { isTransformResponse: false });
}

/** AI 仿写：按当前人设重写公共文案后写入人设文案库，按实际消耗算力扣费 */
export function rewritePublicCopies(data: { persona_id: number | string; ids: number[] }) {
    return request.post({ url: "/copywriting.publicCopy/rewrite", data });
}
