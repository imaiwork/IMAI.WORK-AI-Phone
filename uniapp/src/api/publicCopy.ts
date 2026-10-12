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
