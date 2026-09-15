import { ContactTypeEnum, MissActionEnum, ScanPositionEnum, SearchModeEnum } from './types'
import type { AccountSearchFormData } from './types'

/**
 * 表单 → device.dySearchTask/add 的请求体。
 * 纯函数，不碰 Vue，方便单独跑一遍比对接口文档。
 */
export function buildDySearchPayload(formData: AccountSearchFormData) {
    const isKeywordMode = formData.search_mode === SearchModeEnum.KEYWORD
    const scanCover = formData.scan_positions.includes(ScanPositionEnum.COVER)
    const contactPhone = formData.contact_types.includes(ContactTypeEnum.PHONE)
    // 指定账号模式没有「搜出一批号再筛」这一步，初筛整体不生效
    const preFilter = isKeywordMode && !!formData.pre_filter
    const isMissDm = formData.miss_action === MissActionEnum.DM

    return {
        name: formData.name,
        device_codes: formData.device_codes,
        search_mode: formData.search_mode,
        keywords: isKeywordMode ? formData.keywords : formData.target_accounts,
        per_kw: isKeywordMode ? formData.per_kw : 0,
        scan_config: {
            // pre_filter 关掉时这 4 项必须清零，否则后端仍会按上次填的值过滤
            pre_filter: preFilter,
            fans_min: preFilter ? formData.fans_min : 0,
            works_min: preFilter ? formData.works_min : 0,
            active_days: preFilter ? formData.active_days : 0,
            only_biz: preFilter ? !!formData.only_biz : false,
            scan_bio: formData.scan_positions.includes(ScanPositionEnum.BIO),
            scan_dyid: formData.scan_positions.includes(ScanPositionEnum.DOUYIN_ID),
            scan_cover: scanCover,
            cover_count: formData.cover_count,
            // 只取 1 张就没有「拼」这回事，方向传空串
            stitch: scanCover && formData.cover_count > 1 ? formData.stitch : '',
            contact_phone: contactPhone,
            contact_wechat: formData.contact_types.includes(ContactTypeEnum.WECHAT),
            contact_qq: formData.contact_types.includes(ContactTypeEnum.QQ),
            // 没认手机号就谈不上「拿手机号当微信加」
            phone_as_wechat: contactPhone && !!formData.phone_as_wechat,
            miss_action: formData.miss_action,
            miss_scripts: isMissDm ? formData.miss_scripts : [],
            miss_once: isMissDm && !!formData.miss_once,
            // 去重与已看过跳过：页面无开关，后端锁死，这里照传
            dedupe: true,
            skip_existing: true
        },
        add_type: formData.add_type,
        wechat_id: formData.add_type === 1 ? formData.wechat_id : '',
        add_rule: formData.add_rule,
        add_number: formData.add_number,
        add_interval_time: formData.add_interval_time,
        add_remark_enable: formData.add_remark_enable,
        remarks: formData.add_remark_enable === 1 ? formData.remarks : [],
        task_exec_type: formData.task_exec_type,
        minutes: formData.minutes,
        task_ids: formData.task_ids,
        task_frep: String(formData.task_frep),
        time_config: [`${formData.time_config[0]}-${formData.time_config[1]}`],
        custom_date: formData.custom_date,
        day_cap: formData.day_cap,
        // 排除默认抖音号是硬规则，恒为 1
        exclude_default_id: 1
    }
}
