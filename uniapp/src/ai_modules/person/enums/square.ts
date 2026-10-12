export enum SquareKindEnum {
    VIDEO = 1,
    IMAGE = 2,
}

export enum SquareSourceEnum {
    SQUARE = "square",
}

export const SQUARE_REF_MAX = 200;

export const SQUARE_LIST_ID_PREFIX = "sq-";

export const isSquareMaterial = (item: { source?: string; list_id?: string; id?: string | number }): boolean =>
    item.source === SquareSourceEnum.SQUARE || String(item.list_id || item.id || "").startsWith(SQUARE_LIST_ID_PREFIX);
