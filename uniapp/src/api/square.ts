import request from "@/utils/request";

export function getSquareBrowse(data?: {
    group_id?: number;
    kind?: number;
    page_no?: number;
    page_size?: number;
}) {
    return request.get({ url: "/square.square/lists", data });
}

export function getSquareRefs(data: { persona_id: number | string }) {
    return request.get({ url: "/square.square/refs", data }, { ignoreCancel: true });
}

export function addSquareRefs(data: { persona_id: number | string; ids: number[] }) {
    return request.post({ url: "/square.square/addRefs", data });
}

export function removeSquareRefs(data: { persona_id: number | string; ids: Array<number | string> }) {
    return request.post({ url: "/square.square/removeRefs", data });
}
