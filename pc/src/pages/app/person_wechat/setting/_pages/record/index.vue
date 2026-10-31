<template>
    <div class="h-full flex flex-col gap-y-3 overflow-hidden">
        <div
            class="bg-white h-[72px] flex items-center justify-between px-8 rounded-[20px] border border-br flex-shrink-0"
        >
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 flex items-center justify-center rounded-xl bg-[#0065fb]/10 text-primary mr-3"
                >
                    <Icon name="el-icon-DocumentList" :size="20" />
                </div>
                <div>
                    <div
                        class="text-[18px] text-[#1E293B] font-black tracking-tight"
                    >
                        加微记录
                    </div>
                    <div
                        class="text-[10px] text-[#94A3B8] font-medium uppercase tracking-widest"
                    >
                        Wechat Add Records
                    </div>
                </div>
            </div>
        </div>

        <div class="grow min-h-0 flex flex-col gap-y-3 overflow-hidden">
            <div
                class="flex-shrink-0 p-6 bg-white rounded-[20px] border border-br"
            >
                <div class="flex flex-col gap-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-[4px] h-[18px] bg-primary rounded-full"
                            ></div>
                            <span class="text-[16px] font-black text-[#1E293B]"
                                >记录筛选</span
                            >
                        </div>
                        <div class="flex gap-3">
                            <ElButton
                                type="primary"
                                class="!h-10 !rounded-full px-8 font-medium"
                                @click="handleSearch"
                            >
                                <Icon name="el-icon-Search" />
                                <span class="ml-1">搜索</span>
                            </ElButton>
                            <ElButton
                                class="!h-10 !rounded-full px-8 !bg-gray-100 !border-none !text-tx-regular hover:!bg-gray-200"
                                @click="handleReset"
                            >
                                重置
                            </ElButton>
                        </div>
                    </div>

                    <div class="grid grid-cols-4 gap-4">
                        <div class="filter-item">
                            <div
                                class="text-xs font-medium text-tx-placeholder mb-1.5 ml-1"
                            >
                                执行微信
                            </div>
                            <ElSelect
                                v-model="queryParams.wechat_id"
                                class="custom-select"
                                placeholder="全部微信"
                                filterable
                                clearable
                            >
                                <template #prefix>
                                    <Icon
                                        name="local-icon-wechat"
                                        color="var(--green-500)"
                                    />
                                </template>
                                <ElOption
                                    v-for="item in optionsData.wechatLists"
                                    :key="item.id"
                                    :label="item.wechat_nickname"
                                    :value="item.wechat_id"
                                />
                            </ElSelect>
                        </div>

                        <div class="filter-item">
                            <div
                                class="text-xs font-medium text-tx-placeholder mb-1.5 ml-1"
                            >
                                加微状态
                            </div>
                            <ElSelect
                                v-model="queryParams.status"
                                class="custom-select"
                                placeholder="全部状态"
                                clearable
                            >
                                <ElOption
                                    v-for="(item, key) in AddRecordStatusMap"
                                    :key="key"
                                    :label="item.label"
                                    :value="key"
                                />
                            </ElSelect>
                        </div>

                        <div class="filter-item">
                            <div
                                class="text-xs font-medium text-tx-placeholder mb-1.5 ml-1"
                            >
                                对方微信
                            </div>
                            <ElInput
                                v-model="queryParams.friend_wechat"
                                class="custom-input"
                                placeholder="微信号 / 昵称关键词"
                                clearable
                                @keyup.enter="handleSearch"
                            />
                        </div>

                        <div class="filter-item">
                            <div
                                class="text-xs font-medium text-tx-placeholder mb-1.5 ml-1"
                            >
                                时间范围
                            </div>
                            <ElDatePicker
                                v-model="dateRange"
                                type="daterange"
                                range-separator="-"
                                start-placeholder="开始日期"
                                end-placeholder="结束日期"
                                value-format="YYYY-MM-DD HH:mm:ss"
                                class="custom-date-picker"
                                :default-time="[
                                    new Date(0, 0, 0, 0, 0, 0),
                                    new Date(0, 0, 0, 23, 59, 59)
                                ]"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="grow min-h-0 bg-white rounded-[20px] border border-br flex flex-col overflow-hidden"
            >
                <div class="grow min-h-0">
                    <ElTable
                        height="100%"
                        :data="pager.lists"
                        v-loading="pager.loading"
                    >
                        <ElTableColumn
                            label="执行微信"
                            min-width="170"
                            fixed="left"
                        >
                            <template #default="{ row }">
                                <div class="flex items-center gap-2.5">
                                    <ElAvatar
                                        :size="32"
                                        :src="row.wechat_avatar"
                                        class="flex-shrink-0"
                                    >
                                        <Icon
                                            name="local-icon-wechat"
                                            :size="18"
                                            color="#22c55e"
                                        />
                                    </ElAvatar>
                                    <div class="flex flex-col min-w-0">
                                        <span
                                            class="text-gray-950 font-medium truncate"
                                        >
                                            {{
                                                row.wechat_nickname ||
                                                row.wechat_id ||
                                                '-'
                                            }}
                                        </span>
                                        <span
                                            class="text-[11px] text-tx-placeholder truncate"
                                        >
                                            {{ row.wechat_id || '-' }}
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </ElTableColumn>

                        <ElTableColumn label="渠道" width="100">
                            <template #default="{ row }">
                                <span
                                    v-if="row.channel_name !== '/'"
                                    class="text-tx-regular text-xs bg-gray-50 px-2 py-0.5 rounded border border-br-extra-light"
                                >
                                    {{ row.channel_name }}
                                </span>
                                <span v-else class="text-tx-placeholder text-xs"
                                    >-</span
                                >
                            </template>
                        </ElTableColumn>

                        <ElTableColumn
                            prop="friend_wechat"
                            label="被加微信"
                            min-width="150"
                            show-overflow-tooltip
                        >
                            <template #default="{ row }">
                                <span
                                    class="font-mono font-medium text-primary"
                                    >{{ row.friend_wechat || '-' }}</span
                                >
                            </template>
                        </ElTableColumn>

                        <ElTableColumn
                            prop="message"
                            label="验证消息"
                            min-width="160"
                            show-overflow-tooltip
                        >
                            <template #default="{ row }">
                                <span class="text-tx-regular">{{
                                    row.message || '-'
                                }}</span>
                            </template>
                        </ElTableColumn>

                        <ElTableColumn label="状态" width="100">
                            <template #default="{ row }">
                                <ElTag
                                    :type="
                                        AddRecordStatusMap[row.status]?.color ??
                                        'info'
                                    "
                                    size="small"
                                    effect="light"
                                >
                                    {{
                                        AddRecordStatusMap[row.status]?.label ??
                                        '未知'
                                    }}
                                </ElTag>
                            </template>
                        </ElTableColumn>

                        <ElTableColumn
                            prop="result"
                            label="结果"
                            min-width="160"
                            show-overflow-tooltip
                        >
                            <template #default="{ row }">
                                <span class="text-tx-regular text-xs">{{
                                    row.result || '-'
                                }}</span>
                            </template>
                        </ElTableColumn>

                        <ElTableColumn label="下发时间" width="160">
                            <template #default="{ row }">
                                <span class="text-xs text-tx-placeholder">{{
                                    formatTs(row.exec_time)
                                }}</span>
                            </template>
                        </ElTableColumn>

                        <ElTableColumn label="创建时间" width="160">
                            <template #default="{ row }">
                                <span class="text-xs text-tx-placeholder">{{
                                    formatTs(row.create_time)
                                }}</span>
                            </template>
                        </ElTableColumn>

                        <ElTableColumn
                            label="操作"
                            width="80"
                            fixed="right"
                            align="right"
                        >
                            <template #default="{ row }">
                                <ElButton
                                    type="danger"
                                    link
                                    size="small"
                                    class="!font-medium"
                                    @click="handleDelete(row.id)"
                                >
                                    删除
                                </ElButton>
                            </template>
                        </ElTableColumn>
                    </ElTable>
                </div>

                <div
                    class="shrink-0 h-[72px] px-8 flex items-center justify-between"
                >
                    <div class="text-xs font-medium text-[#CBD5E1]">
                        共计 {{ pager.count }} 条加微记录
                    </div>
                    <pagination
                        v-model="pager"
                        layout="prev, pager, next"
                        @change="getLists"
                    ></pagination>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import {
    getAddRecordLists,
    deleteAddRecord,
    getWeChatLists
} from '@/api/person_wechat'
import { AddRecordStatusMap } from '@/pages/app/person_wechat/_enums'
import dayjs from 'dayjs'

const nuxtApp = useNuxtApp()

// 时间范围选择值，watch 联动查询参数
const dateRange = ref<[string, string] | null>(null)

const queryParams = reactive({
    wechat_id: '',
    status: '',
    friend_wechat: '',
    start_time: '',
    end_time: '',
    page_size: 20
})

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getAddRecordLists,
    params: queryParams,
    size: queryParams.page_size
})

const { optionsData } = useDictOptions<{
    wechatLists: any[]
}>({
    wechatLists: {
        api: getWeChatLists,
        params: {
            page_size: 999
        },
        transformData: (data) => data.lists
    }
})

watch(dateRange, (val) => {
    queryParams.start_time = val?.[0] ?? ''
    queryParams.end_time = val?.[1] ?? ''
})

const formatTs = (ts: number | string) => {
    if (!ts || ts === '-') return '-'
    // 模型 create_time/update_time 是自动格式化后的字符串时间，exec_time 是秒级 int
    if (typeof ts === 'string') {
        const d = dayjs(ts)
        return d.isValid() ? d.format('YYYY-MM-DD HH:mm') : ts
    }
    return Number(ts) > 0
        ? dayjs(Number(ts) * 1000).format('YYYY-MM-DD HH:mm')
        : '-'
}

// 搜索：保持筛选条件，回到第一页重新查询
const handleSearch = () => resetPage()

// 重置：清空筛选条件 + 时间范围，回到第一页
const handleReset = () => {
    dateRange.value = null
    queryParams.wechat_id = ''
    queryParams.status = ''
    queryParams.friend_wechat = ''
    resetPage()
}

const handleDelete = (id: number | string) => {
    nuxtApp.$confirm({
        message: '确定删除该加微记录吗？',
        onConfirm: async () => {
            try {
                await deleteAddRecord({ id })
                feedback.msgSuccess('删除成功')
                getLists()
            } catch (error) {
                feedback.msgError(error || '删除失败')
            }
        }
    })
}

getLists()
</script>

<style lang="scss" scoped>
.filter-item {
    :deep(.el-select),
    :deep(.el-input),
    :deep(.el-date-editor) {
        width: 100%;
        --el-input-height: 40px;
    }
}
</style>
