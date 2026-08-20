/**
 * 工作台扣费(图片创作 / PPT 生成 等"按数量"场景)
 *
 * 后端端点:POST /api/ai_billing/deduct
 * 返回:{ deducted, unit_price, current_tokens }
 */

export type AiBillingScene = "gpt_image_gen" | "gpt_ppt_slide" | "gpt_map_lead";

export interface AiBillingResult {
    deducted: number;
    unit_price: number;
    current_tokens: number;
}

/** 扣费(成功才返回剩余算力,失败由请求拦截层抛出) */
export function deductAiBilling(params: {
    scene: AiBillingScene;
    count: number;
    task_id?: string;
    meta?: Record<string, any>;
}) {
    return $request.post<AiBillingResult>({
        url: "/ai_billing/deduct",
        params,
    });
}
