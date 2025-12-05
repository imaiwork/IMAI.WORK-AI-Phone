import type { PropsItem } from './props'

/**
 * @description 物料控件基本配置
 */
export interface WidgetMeta {
    /**
     * 标题
     */
    title: string
    /**
     * 唯一标识
     */
    id?: string
    /**
     * 注册的组件名称
     */
    name: string
    /**
     * 排序 越小越前面
     */
    sort: number

    props: PropsItem[]
}

/**
 * @description 已经解析的
 */
export interface WidgetNormalization extends Pick<WidgetMeta, 'title' | 'name'> {
    /**
     * 唯一标识
     */
    id: string
    /**
     * 排序 越小越前面
     */
    props: Record<string, any>
}
