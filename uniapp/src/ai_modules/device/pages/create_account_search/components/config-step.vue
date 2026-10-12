<template>
    <view class="flex flex-col gap-[16rpx]">
        <!-- 光是收起还不够——用户不点是不知道这些行能展开的，得说一句 -->
        <view class="flex items-center gap-[16rpx] px-[8rpx]">
            <text class="flex-1 min-w-0 text-[22rpx] text-[#9CA3AF] leading-relaxed">
                点一行展开修改，右边灰字是当前设置
            </text>
            <view
                class="flex-shrink-0 px-[24rpx] py-[10rpx] rounded-full bg-[#EBF2FF] border border-solid border-[#D6E4FF]"
                @click="handleToggleAll"
            >
                <text class="text-[24rpx] font-semibold text-primary">
                    {{ allOpen ? '全部收起' : '全部展开' }}
                </text>
            </view>
        </view>

        <!-- ── 看哪里 ── -->
        <accordion-card
            title="查看哪些位置"
            sub="进到账号主页后，从这几处找联系方式"
            :value="summary.scan.value"
            :warn="summary.scan.warn"
            :open="isOpen(CardKey.SCAN)"
            @toggle="handleToggle(CardKey.SCAN)"
        >
            <view class="px-[28rpx] py-[16rpx]">
                <check-row
                    v-for="item in VISIBLE_SCAN_POSITION_LIST"
                    :key="item.value"
                    :checked="formData.scan_positions.includes(item.value)"
                    :name="item.name"
                    :desc="item.desc"
                    @change="handleToggleScan(item.value)"
                />

                <!-- 「置顶作品封面」及其张数/拼接方向/示意图按需求先不露出，
                     scan_cover / cover_count / stitch 仍按默认值随 scan_config 提交 -->
            </view>
        </accordion-card>

        <!-- ── 认什么 ── -->
        <accordion-card
            title="识别哪些联系方式"
            :value="summary.contact.value"
            :warn="summary.contact.warn"
            :open="isOpen(CardKey.CONTACT)"
            @toggle="handleToggle(CardKey.CONTACT)"
        >
            <view class="px-[28rpx] py-[16rpx]">
                <check-row
                    v-for="item in CONTACT_TYPE_LIST"
                    :key="item.value"
                    :checked="formData.contact_types.includes(item.value)"
                    :name="item.name"
                    :desc="item.desc"
                    @change="handleToggleContact(item.value)"
                />

                <view
                    v-if="isPhoneOn"
                    class="mt-[24rpx] pt-[24rpx] border-[0] border-t border-solid border-[#F4F6F9]"
                >
                    <check-row
                        :checked="!!formData.phone_as_wechat"
                        name="手机号同时当微信号去加"
                        desc="这类账号大多手机号就是微信号。加不上只记一次失败，不影响这条客资继续留在库里。"
                        @change="formData.phone_as_wechat = formData.phone_as_wechat ? 0 : 1"
                    />
                </view>
            </view>
        </accordion-card>

        <!-- ── 排除规则 ── -->
        <accordion-card
            title="排除规则"
            value="默认抖音号不算"
            :open="isOpen(CardKey.EXCLUDE)"
            @toggle="handleToggle(CardKey.EXCLUDE)"
        >
            <view class="px-[28rpx] py-[16rpx]">
                <check-row
                    checked
                    lock
                    name="默认抖音号不算联系方式"
                    desc="系统分配的号长得像号码，但没有任何联系意义。这条是硬规则，不能关。"
                />

                <view
                    v-if="isDouyinIdOn"
                    class="mt-[20rpx] rounded-[16rpx] border border-solid border-[#EEF1F6] overflow-hidden"
                >
                    <view
                        v-for="item in ID_EXAMPLES"
                        :key="item.id"
                        class="flex items-center gap-[12rpx] px-[22rpx] py-[16rpx] border-[0] border-b border-solid border-[#F4F6F9] last:border-[0]"
                    >
                        <text class="flex-1 min-w-0 text-[22rpx] text-[#0D1117] line-clamp-1">
                            {{ item.id }}
                        </text>
                        <view
                            class="flex-shrink-0 rounded-[10rpx] px-[14rpx] py-[6rpx]"
                            :class="item.ok ? 'bg-[#E7F8F1]' : 'bg-[#FEECEC]'"
                        >
                            <text
                                class="text-[20rpx] font-bold"
                                :class="item.ok ? 'text-[#00875A]' : 'text-[#D93026]'"
                            >
                                {{ item.ok ? '认' : '不认' }} · {{ item.why }}
                            </text>
                        </view>
                    </view>
                </view>
            </view>
        </accordion-card>

        <!-- ── 没识别到怎么办 ── -->
        <accordion-card
            title="没识别到联系方式时"
            sub="两处都没找到，接下来怎么办"
            :value="summary.miss.value"
            :open="isOpen(CardKey.MISS)"
            @toggle="handleToggle(CardKey.MISS)"
        >
            <view class="px-[28rpx] py-[24rpx]">
                <view class="flex bg-[#F0F2F5] rounded-[20rpx] p-[6rpx] gap-[6rpx]">
                    <view
                        v-for="item in MISS_ACTION_LIST"
                        :key="item.value"
                        class="flex-1 h-[72rpx] flex items-center justify-center rounded-[16rpx] font-semibold transition-all duration-200"
                        :class="
                            formData.miss_action === item.value
                                ? 'bg-white text-primary shadow-[0_2rpx_8rpx_rgba(0,0,0,0.08)]'
                                : 'text-[#9CA3AF]'
                        "
                        @click="handlePickMiss(item.value)"
                    >
                        <text class="text-[27rpx]">{{ item.label }}</text>
                    </view>
                </view>

                <template v-if="isMissDm">
                    <text class="block mt-[28rpx] mb-[20rpx] text-xs text-[#9CA3AF]">
                        私信话术（随机取一条发）
                    </text>
                    <chip-list
                        :list="formData.miss_scripts"
                        empty-text="还没有私信话术，点这里添加"
                        @add="emit('add-miss-dm')"
                        @edit="emit('edit-miss-dm', $event)"
                        @remove="formData.miss_scripts.splice($event, 1)"
                    />

                    <view
                        class="mt-[24rpx] pt-[24rpx] border-[0] border-t border-solid border-[#F4F6F9]"
                    >
                        <check-row
                            :checked="!!formData.miss_once"
                            name="同一个账号只问一次"
                            desc="问过没回的记进已问列表，下次搜到同一个号不再打扰"
                            @change="formData.miss_once = formData.miss_once ? 0 : 1"
                        />
                    </view>

                    <view
                        class="mt-[24rpx] rounded-[16rpx] bg-[#FFF8E9] border border-solid border-[#FFE7BA] px-[22rpx] py-[20rpx]"
                    >
                        <text class="text-[22rpx] text-[#AD6800] leading-relaxed">
                            私信要占账号的私信额度，也更容易触发风控。建议配合第 4
                            步的单日上限一起用。
                        </text>
                    </view>
                </template>
                <view
                    v-else
                    class="mt-[24rpx] rounded-[16rpx] bg-[#F2F7FF] border border-solid border-[#D6E6FF] px-[22rpx] py-[20rpx]"
                >
                    <text class="text-[22rpx] text-[#2A5FAE] leading-relaxed">
                        不打扰，账号记进已看过列表，下次搜到同一个号直接跳过，不重复点进去浪费时间。
                    </text>
                </view>
            </view>
        </accordion-card>

        <!-- ── 识别到之后 ── -->
        <accordion-card
            title="自动添加好友"
            sub="识别到联系方式后生成加好友任务，按下方节奏执行"
            :value="summary.add.value"
            :warn="summary.add.warn"
            :open="isOpen(CardKey.ADD)"
            @toggle="handleToggle(CardKey.ADD)"
        >
            <template #head>
                <view class="flex-shrink-0" @click.stop>
                    <u-switch v-model="autoAdd" size="42" active-color="#0065fb" />
                </view>
            </template>

            <view v-if="formData.add_type === 1" class="px-[28rpx] py-[24rpx]">
                <text class="block mb-[12rpx] text-xs text-[#9CA3AF]">加微微信</text>
                <data-select v-model="selectedWechatIds" multiple :localdata="wechatOptions" />

                <text class="block mt-[28rpx] mb-[20rpx] text-xs text-[#9CA3AF]">加微规则</text>
                <option-grid v-model="formData.add_rule" :options="addRuleOptions" :columns="2" />

                <view class="flex gap-[24rpx] mt-[28rpx]">
                    <view class="flex-1 min-w-0">
                        <text class="block mb-[12rpx] text-xs text-[#9CA3AF]">当前执行</text>
                        <view
                            class="flex items-center gap-[12rpx] bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] h-[80rpx] border border-solid border-[#E5E9F0]"
                        >
                            <u-input
                                v-model="formData.add_number"
                                type="number"
                                class="flex-1"
                                @blur="handleNumBlur('add_number')"
                            />
                            <text class="text-[24rpx] text-[#86909C] flex-shrink-0">次</text>
                        </view>
                    </view>
                    <view class="flex-1 min-w-0">
                        <text class="block mb-[12rpx] text-xs text-[#9CA3AF]">每次间隔</text>
                        <view
                            class="flex items-center gap-[12rpx] bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] h-[80rpx] border border-solid border-[#E5E9F0]"
                        >
                            <u-input
                                v-model="formData.add_interval_time"
                                type="number"
                                class="flex-1"
                                @blur="handleNumBlur('add_interval_time')"
                            />
                            <text class="text-[24rpx] text-[#86909C] flex-shrink-0">分钟</text>
                        </view>
                    </view>
                </view>

                <view
                    v-if="!formData.wechat_id"
                    class="mt-[24rpx] rounded-[16rpx] bg-[#FFF8E9] border border-solid border-[#FFE7BA] px-[22rpx] py-[20rpx]"
                >
                    <text class="text-[22rpx] text-[#AD6800] leading-relaxed">
                        还没选加微微信，任务会存下来但不会执行。
                    </text>
                </view>
            </view>
            <view v-else class="px-[28rpx] py-[24rpx]">
                <text class="text-[22rpx] text-[#9CA3AF] leading-relaxed">
                    已关闭。识别到的联系方式只存进客资库，不会自动去加。
                </text>
            </view>
        </accordion-card>

        <!-- ── 好友验证语 ── -->
        <accordion-card
            title="加好友备注内容"
            sub="好友验证语，随机取一条发"
            :value="summary.remark.value"
            :open="isOpen(CardKey.REMARK)"
            @toggle="handleToggle(CardKey.REMARK)"
        >
            <template #head>
                <view class="flex-shrink-0" @click.stop>
                    <u-switch v-model="remarkOn" size="42" active-color="#0065fb" />
                </view>
            </template>

            <view v-if="formData.add_remark_enable === 1" class="px-[28rpx] py-[24rpx]">
                <chip-list
                    :list="formData.remarks"
                    add-label="新增备注"
                    empty-text="还没有验证语，点这里添加"
                    @add="emit('add-remark')"
                    @edit="emit('edit-remark', $event)"
                    @remove="formData.remarks.splice($event, 1)"
                />
                <text class="block mt-[24rpx] text-[22rpx] text-[#9CA3AF] leading-relaxed">
                    带上来源（「抖音看到的」）通过率明显更高。
                </text>
            </view>
            <view v-else class="px-[28rpx] py-[24rpx]">
                <text class="text-[22rpx] text-[#9CA3AF] leading-relaxed">
                    已关闭。发好友申请时不带验证语，通过率会低一些。
                </text>
            </view>
        </accordion-card>
    </view>
</template>

<script setup lang="ts">
import AccordionCard from './accordion-card.vue'
import CheckRow from './check-row.vue'
import ChipList from './chip-list.vue'
import OptionGrid from './option-grid.vue'
import {
    ADD_RULE_LIST,
    CONTACT_TYPE_LIST,
    ID_EXAMPLES,
    VISIBLE_SCAN_POSITION_LIST,
    ContactTypeEnum,
    MissActionEnum,
    ScanPositionEnum,
    clampNumField
} from '../hooks/types'
import type { AccountSearchFormData, NumFieldKey } from '../hooks/types'

/** 六张卡的标识，只在这一屏用 */
enum CardKey {
    SCAN = 'scan',
    CONTACT = 'contact',
    EXCLUDE = 'exclude',
    MISS = 'miss',
    ADD = 'add',
    REMARK = 'remark'
}

const MISS_ACTION_LIST = [
    { value: MissActionEnum.DM, label: '私信主动问' },
    { value: MissActionEnum.SKIP, label: '直接跳过' }
]

const props = defineProps<{
    modelValue: AccountSearchFormData
    /** 加微客服微信下拉项，由页面统一拉取后灌进来 */
    wechatOptions: { text: string; value: string }[]
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: AccountSearchFormData): void
    (e: 'add-miss-dm'): void
    (e: 'add-remark'): void
    (e: 'edit-miss-dm', index: number): void
    (e: 'edit-remark', index: number): void
}>()

const formData = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
})

// 多选组件使用数组，表单和提交接口使用逗号分隔的微信账号。
const selectedWechatIds = computed({
    get: () => formData.value.wechat_id.split(',').filter(Boolean),
    set: (value: string[]) => (formData.value.wechat_id = value.join(','))
})

/**
 * 六张卡一路铺下去太长，滚到后面就忘了前面配的是什么。默认全收起，
 * 一屏看完六项和各自的当前值；一次只展开一张，页面长度就稳住了。
 */
const openCard = ref<CardKey | null>(null)
const allOpen = ref(false)

const isOpen = (key: CardKey) => allOpen.value || openCard.value === key

const handleToggle = (key: CardKey) => {
    if (allOpen.value) {
        // 从全展开点某一张 = 只留这张
        allOpen.value = false
        openCard.value = key
        return
    }
    openCard.value = openCard.value === key ? null : key
}

const handleToggleAll = () => {
    allOpen.value = !allOpen.value
    openCard.value = null
}

const isDouyinIdOn = computed(() =>
    formData.value.scan_positions.includes(ScanPositionEnum.DOUYIN_ID)
)
const isPhoneOn = computed(() => formData.value.contact_types.includes(ContactTypeEnum.PHONE))
const isMissDm = computed(() => formData.value.miss_action === MissActionEnum.DM)

const addRuleOptions = ADD_RULE_LIST


/** 收起行右边那句「当前值」：不展开也知道配的是什么 */
const summary = computed(() => {
    const scanNames = VISIBLE_SCAN_POSITION_LIST.filter((item) =>
        formData.value.scan_positions.includes(item.value)
    ).map((item) => item.name)
    const contactNames = CONTACT_TYPE_LIST.filter((item) =>
        formData.value.contact_types.includes(item.value)
    ).map((item) => item.name)

    return {
        scan: scanNames.length
            ? { value: scanNames.join(' / '), warn: false }
            : { value: '一处都没选', warn: true },
        contact: contactNames.length
            ? { value: contactNames.join(' / '), warn: false }
            : { value: '一种都没选', warn: true },
        miss: {
            value: isMissDm.value
                ? `私信主动问 · ${formData.value.miss_scripts.length} 条话术`
                : '直接跳过',
            warn: false
        },
        add:
            formData.value.add_type !== 1
                ? { value: '不自动加', warn: false }
                : wechatNames.value
                ? { value: wechatNames.value, warn: false }
                : { value: '已开 · 未选微信', warn: true },
        remark: {
            value:
                formData.value.add_remark_enable === 1
                    ? `${formData.value.remarks.length} 条话术`
                    : '不带备注',
            warn: false
        }
    }
})

const autoAdd = computed({
    get: () => formData.value.add_type === 1,
    set: (value: boolean) => (formData.value.add_type = value ? 1 : 0)
})

const remarkOn = computed({
    get: () => formData.value.add_remark_enable === 1,
    set: (value: boolean) => (formData.value.add_remark_enable = value ? 1 : 0)
})

/** wechat_id 存的是 account(wxid) 逗号分隔，摘要里换回昵称 */
const wechatNames = computed(() => {
    if (!formData.value.wechat_id) return ''
    return formData.value.wechat_id
        .split(',')
        .filter(Boolean)
        .map((id) => props.wechatOptions.find((item) => item.value === id)?.text ?? id)
        .join('、')
})

const handleToggleScan = (key: ScanPositionEnum) => {
    const list = formData.value.scan_positions
    const index = list.indexOf(key)
    if (index >= 0) {
        // 只数露出来的那几项：隐藏的封面一直在 list 里，拿总长度判会把两行都放开
        const visibleChecked = VISIBLE_SCAN_POSITION_LIST.filter((item) =>
            list.includes(item.value)
        ).length
        if (visibleChecked === 1) return uni.$u.toast('至少要留一个查看位置')
        list.splice(index, 1)
        return
    }
    list.push(key)
}

const handleToggleContact = (key: ContactTypeEnum) => {
    const list = formData.value.contact_types
    const index = list.indexOf(key)
    if (index >= 0) {
        if (list.length === 1) return uni.$u.toast('至少要认一种联系方式')
        list.splice(index, 1)
        return
    }
    list.push(key)
}

const handlePickMiss = (value: MissActionEnum) => {
    formData.value.miss_action = value
    uni.$u.toast(
        value === MissActionEnum.DM ? '没识别到时会主动私信问' : '没识别到时直接跳过，不打扰'
    )
}

const handleNumBlur = (key: NumFieldKey) => {
    formData.value[key] = clampNumField(key, formData.value[key])
}
</script>

<style scoped></style>
