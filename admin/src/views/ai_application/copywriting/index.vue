<template>
    <div class="copy-page flex h-full min-h-[640px] bg-page">
        <aside class="w-[220px] flex-shrink-0 bg-white border-r border-[#E4E7ED] flex flex-col">
            <div class="h-14 px-4 flex items-center gap-2 flex-shrink-0">
                <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-white text-sm">文</div>
                <span class="text-base font-bold text-[#303133]">公共文案库</span>
            </div>
            <div class="px-3 py-2 text-xs text-[#C0C4CC]">类型</div>
            <div class="flex-1 min-h-0 overflow-y-auto px-2 pb-3">
                <button
                    v-for="item in SIDE_FILTERS"
                    :key="item.key"
                    class="side-item"
                    :class="{ on: currentKey === item.key }"
                    @click="handleSwitchType(item.key)">
                    <span>{{ item.label }}</span>
                    <span class="text-xs text-[#909399]">{{ typeCount(item) }}</span>
                </button>
            </div>
        </aside>

        <main class="flex-1 min-w-0 flex flex-col">
            <header class="h-14 px-5 bg-white border-b border-[#E4E7ED] flex items-center gap-3 flex-shrink-0">
                <div class="text-sm text-[#909399]">内容运营 /</div>
                <h1 class="text-[15px] font-semibold text-[#303133]">公共文案库</h1>
                <el-tag type="info" size="small">{{ pager.count }} 条</el-tag>
                <div class="ml-auto flex items-center gap-2">
                    <el-button type="primary" @click="openDrawer(null)">新建文案</el-button>
                </div>
            </header>

            <div class="flex-1 min-h-0 overflow-y-auto p-5">
                <div class="bg-white rounded-lg border border-[#EBEEF5] mb-4 px-4 py-3 text-sm text-[#606266]">
                    保存即发布；客户自己勾选才会写入人设文案库。改正文 / 标题 / 类型会升版，停用不影响已加入的。
                </div>

                <div class="bg-white rounded-lg overflow-x-auto">
                    <el-table :data="pager.lists" v-loading="pager.loading" empty-text="这一类还没有文案">
                        <el-table-column prop="title" label="标题" min-width="160" show-overflow-tooltip />
                        <el-table-column label="文案内容" min-width="280">
                            <template #default="{ row }">
                                <div class="copy-body" v-html="highlightPlaceholders(row.content || '')" />
                            </template>
                        </el-table-column>
                        <el-table-column label="类型" width="110">
                            <template #default="{ row }">
                                <el-tag :type="Number(row.ui_type) === UI_TYPE_FRAME ? 'warning' : 'primary'" size="small">
                                    {{ uiTypeName(row.ui_type) }}
                                </el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="入库分类" width="120">
                            <template #default="{ row }">{{ libraryTypeName(row) }}</template>
                        </el-table-column>
                        <el-table-column label="行业" width="90">
                            <template #default="{ row }">{{ row.industry || '通用' }}</template>
                        </el-table-column>
                        <el-table-column label="版本" width="70">
                            <template #default="{ row }">v{{ row.version }}</template>
                        </el-table-column>
                        <el-table-column label="状态" width="90">
                            <template #default="{ row }">
                                <el-tag :type="row.enabled ? 'success' : 'info'" size="small">
                                    {{ row.enabled ? '已启用' : '已停用' }}
                                </el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="操作" width="180" fixed="right">
                            <template #default="{ row }">
                                <el-button link type="primary" @click="openDrawer(row)">编辑</el-button>
                                <el-button link type="primary" @click="toggleRow(row)">
                                    {{ row.enabled ? '停用' : '启用' }}
                                </el-button>
                                <el-button link type="danger" @click="removeRow(row)">删除</el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                    <div class="flex justify-end px-4 py-3">
                        <pagination v-model="pager" @change="getLists" />
                    </div>
                </div>
            </div>
        </main>

        <copy-drawer v-model="drawerVisible" :row="editingRow" :default-ui-type="defaultUiType" @saved="refreshLists" />
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessageBox } from 'element-plus'
import feedback from '@/utils/feedback'
import { usePaging } from '@/hooks/usePaging'
import { deletePublicCopy, getPublicCopies, updatePublicCopy } from '@/api/ai_application/copywriting'
import CopyDrawer from './components/copy-drawer.vue'
import {
    UI_TYPE_FRAME,
    UI_TYPE_READY,
    highlightPlaceholders,
    libraryTypeName,
    uiTypeName,
    type PublicCopyRow
} from './constants'

const SIDE_FILTERS = [
    { key: 'all', label: '全部', ui_type: 0 },
    { key: 'ready', label: '成品文案', ui_type: UI_TYPE_READY },
    { key: 'frame', label: '提示词框架', ui_type: UI_TYPE_FRAME }
] as const

const drawerVisible = ref(false)
const editingRow = ref<PublicCopyRow | null>(null)
const queryParams = reactive({
    ui_type: 0
})

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getPublicCopies,
    params: queryParams
})

const currentFilter = computed(() => SIDE_FILTERS.find((item) => item.ui_type === queryParams.ui_type) || SIDE_FILTERS[0])
const currentKey = computed(() => currentFilter.value.key)
const defaultUiType = computed(() => currentFilter.value.ui_type || UI_TYPE_READY)

const typeCount = (filter: (typeof SIDE_FILTERS)[number]) => {
    const counts = pager.extend || {}
    if (!filter.ui_type) return Number(counts.all ?? pager.count)
    if (filter.ui_type === UI_TYPE_READY) return Number(counts.ready ?? 0)
    return Number(counts.frame ?? 0)
}

const handleSwitchType = (key: (typeof SIDE_FILTERS)[number]['key']) => {
    const next = SIDE_FILTERS.find((item) => item.key === key)?.ui_type ?? 0
    if (queryParams.ui_type === next) return
    queryParams.ui_type = next
    resetPage()
}

const refreshLists = async () => {
    await getLists()
    if (pager.page > 1 && !pager.lists.length) {
        pager.page -= 1
        await getLists()
    }
}

const openDrawer = (row: PublicCopyRow | null) => {
    editingRow.value = row
    drawerVisible.value = true
}

const toggleRow = async (row: any) => {
    await updatePublicCopy({ id: row.id, enabled: row.enabled ? 0 : 1 })
    feedback.msgSuccess(row.enabled ? '已停用' : '已启用')
    await refreshLists()
}

const removeRow = async (row: any) => {
    await ElMessageBox.confirm('删除后客户再也挑不到，已经加入人设的不会被删。确定删除？', '删除文案', {
        type: 'warning',
        confirmButtonText: '删除',
        confirmButtonClass: 'el-button--danger'
    })
    await deletePublicCopy({ id: row.id })
    feedback.msgSuccess('已删除')
    await refreshLists()
}

onMounted(getLists)
</script>

<style lang="scss" scoped>
.side-item {
    @apply w-full mb-1 px-3 py-2 rounded-md flex items-center justify-between gap-2 text-sm text-[#303133];
    &.on {
        @apply bg-[#ECF5FF] text-primary;
    }
}
.copy-body {
    display: -webkit-box;
    -webkit-line-clamp: 4;
    -webkit-box-orient: vertical;
    overflow: hidden;
    white-space: pre-line;
    word-break: break-all;
    line-height: 1.6;
    :deep(.ph) {
        @apply inline-block px-1 mx-px rounded bg-[#ECF5FF] text-primary border border-solid border-[#D9ECFF] text-xs;
    }
    :deep(.ph.bad) {
        @apply bg-[#FDF6EC] text-[#E6A23C] border-[#FAECD8];
    }
}
</style>
