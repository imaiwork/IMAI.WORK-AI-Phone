<template>
    <div class="square-page flex h-full min-h-[640px] bg-page">
        <aside class="w-[220px] flex-shrink-0 bg-white border-r border-[#E4E7ED] flex flex-col">
            <div class="h-14 px-4 flex items-center gap-2 flex-shrink-0">
                <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-white text-sm">广</div>
                <span class="text-base font-bold text-[#303133]">素材库广场</span>
            </div>
            <div class="px-3 py-2 flex items-center justify-between">
                <span class="text-xs text-[#C0C4CC]">分组</span>
                <el-button link type="primary" @click="openGroup(null)">+ 新建分组</el-button>
            </div>
            <div class="flex-1 min-h-0 overflow-y-auto px-2 pb-3">
                <button
                    class="side-item"
                    :class="{ on: currentGroupId === 0 }"
                    @click="handleSwitchGroup(0)">
                    <span>全部素材</span>
                    <span class="text-xs text-[#909399]">{{ Number(pager.extend.asset_total ?? assetTotal) }}</span>
                </button>
                <button
                    v-for="item in groups"
                    :key="item.id"
                    class="side-item"
                    :class="{ on: currentGroupId === item.id, off: !item.enabled }"
                    @click="handleSwitchGroup(item.id)">
                    <span class="truncate">{{ item.name }}</span>
                    <el-tag v-if="!item.enabled" size="small" type="info">已下架</el-tag>
                    <span v-else class="text-xs text-[#909399]">{{ item.asset_count || 0 }}</span>
                </button>
            </div>
        </aside>

        <main class="flex-1 min-w-0 flex flex-col">
            <header class="h-14 px-5 bg-white border-b border-[#E4E7ED] flex items-center gap-3 flex-shrink-0">
                <h1 class="text-[15px] font-semibold text-[#303133]">{{ currentTitle }}</h1>
                <el-tag type="info" size="small">{{ pager.count }} 个</el-tag>
                <div class="ml-auto flex items-center gap-2">
                    <el-select v-model="queryParams.kind" class="!w-[110px]" size="default" @change="resetPage">
                        <el-option label="全部类型" :value="0" />
                        <el-option label="视频" :value="1" />
                        <el-option label="图片" :value="2" />
                    </el-select>
                    <el-button v-if="currentGroupId" @click="openGroup(currentGroup)">编辑分组</el-button>
                    <el-button type="primary" @click="openAsset(null)">添加素材</el-button>
                </div>
            </header>

            <div class="flex-1 min-h-0 overflow-y-auto p-5">
                <div class="bg-white rounded-lg border border-[#EBEEF5] mb-4">
                    <div class="px-4 py-3 flex items-center">
                        <span class="text-sm font-semibold text-[#303133]">怎么操作</span>
                        <el-button link type="primary" class="ml-auto" @click="guideOpen = !guideOpen">
                            {{ guideOpen ? '收起' : '展开' }}
                        </el-button>
                    </div>
                    <div v-if="guideOpen" class="px-4 pb-4 text-sm text-[#606266] leading-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                            <div class="rounded-md bg-[#F5F7FA] p-3">
                                <div class="font-semibold mb-1">1 建分组</div>
                                分组名就是客户在广场里看到的筛选标签，起个一看就懂的，比如「装修 · 施工现场」。
                            </div>
                            <div class="rounded-md bg-[#F5F7FA] p-3">
                                <div class="font-semibold mb-1">2 传素材、上架</div>
                                点「添加素材」：选分组 → 一次多个视频 / 图片 → 等进度走完 → 点「上架给客户」。
                            </div>
                            <div class="rounded-md bg-[#F5F7FA] p-3">
                                <div class="font-semibold mb-1">3 客户那边怎么用</div>
                                人设详情 › 素材库 › 画面素材 › 从素材库广场添加，加入的是引用，不占他的上传空间。
                            </div>
                        </div>
                        <div class="text-xs text-[#909399] leading-5">
                            改名称、换文件、换封面——所有加过的客户那边跟着变。下架 / 删除后客户那边灰卡「已下架」，不会凭空消失。
                            分组下架会级联隐藏组内素材。AI 员工选「素材库+广场」时，自有素材与广场素材对半使用，不够再用另一边补缺口，不用客户先加入广场。
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg">
                    <el-table :data="pager.lists" v-loading="pager.loading" empty-text="这一组还没有素材">
                        <el-table-column label="封面" width="80">
                            <template #default="{ row }">
                                <el-image
                                    :src="row.cover_url || (row.kind === 2 ? row.url : '')"
                                    class="w-[52px] h-[70px] rounded object-cover bg-[#F2F3F5]"
                                    fit="cover" />
                            </template>
                        </el-table-column>
                        <el-table-column prop="name" label="名称" min-width="160" show-overflow-tooltip />
                        <el-table-column label="类型" width="90">
                            <template #default="{ row }">{{ row.kind === 1 ? '视频' : '图片' }}</template>
                        </el-table-column>
                        <el-table-column prop="group_name" label="分组" width="120" show-overflow-tooltip />
                        <el-table-column label="时长" width="80">
                            <template #default="{ row }">
                                {{ row.kind === 1 ? formatDuration(row.duration_sec) : '-' }}
                            </template>
                        </el-table-column>
                        <el-table-column label="标签" min-width="150">
                            <template #default="{ row }">
                                <el-tag
                                    v-for="tag in row.tags || []"
                                    :key="tag"
                                    size="small"
                                    class="mr-1 mb-1"
                                    type="info">
                                    {{ tag }}
                                </el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="状态" width="90">
                            <template #default="{ row }">
                                <el-tag :type="row.enabled ? 'success' : 'info'" size="small">
                                    {{ row.enabled ? '上架' : '下架' }}
                                </el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="操作" width="180" fixed="right">
                            <template #default="{ row }">
                                <el-button link type="primary" @click="openAsset(row)">编辑</el-button>
                                <el-button link type="primary" @click="toggleAsset(row)">
                                    {{ row.enabled ? '下架' : '上架' }}
                                </el-button>
                                <el-button link type="danger" @click="removeAsset(row)">删除</el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                    <div class="flex justify-end px-4 py-3">
                        <pagination v-model="pager" @change="getLists" />
                    </div>
                </div>
            </div>
        </main>

        <group-drawer v-model="groupVisible" :group="editingGroup" @saved="reload" />
        <asset-drawer
            v-model="assetVisible"
            :groups="groups"
            :asset="editingAsset"
            :default-group-id="currentGroupId"
            @saved="reload"
            @group-created="loadGroups" />
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessageBox } from 'element-plus'
import feedback from '@/utils/feedback'
import { usePaging } from '@/hooks/usePaging'
import { deleteSquareAsset, getSquareAssets, getSquareGroups, updateSquareAsset } from '@/api/ai_application/square'
import GroupDrawer, { type SquareGroupRow } from './components/group-drawer.vue'
import AssetDrawer from './components/asset-drawer.vue'

const groups = ref<SquareGroupRow[]>([])
const assetTotal = ref(0)
const guideOpen = ref(true)
const groupVisible = ref(false)
const assetVisible = ref(false)
const editingGroup = ref<SquareGroupRow | null>(null)
const editingAsset = ref<any>(null)
const queryParams = reactive({
    group_id: 0,
    kind: 0
})

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getSquareAssets,
    params: queryParams
})

const currentGroupId = computed(() => queryParams.group_id)
const currentGroup = computed(() => groups.value.find((item) => item.id === currentGroupId.value) || null)
const currentTitle = computed(() => currentGroup.value?.name || '全部素材')

const loadGroups = async () => {
    const res: any = await getSquareGroups()
    groups.value = res?.groups || []
    assetTotal.value = Number(res?.asset_total || 0)
}

const refreshLists = async () => {
    await getLists()
    if (pager.page > 1 && !pager.lists.length) {
        pager.page -= 1
        await getLists()
    }
}

const reload = async () => {
    await Promise.all([loadGroups(), refreshLists()])
}

const handleSwitchGroup = (id: number) => {
    if (queryParams.group_id === id) return
    queryParams.group_id = id
    resetPage()
}

const openGroup = (group: SquareGroupRow | null) => {
    editingGroup.value = group
    groupVisible.value = true
}

const openAsset = (asset: any) => {
    editingAsset.value = asset
    assetVisible.value = true
}

const toggleAsset = async (row: any) => {
    await updateSquareAsset({ id: row.id, enabled: row.enabled ? 0 : 1 })
    feedback.msgSuccess(row.enabled ? '已下架' : '已上架')
    await reload()
}

const removeAsset = async (row: any) => {
    await ElMessageBox.confirm('删除后已引用的客户那边会显示「已下架」，确定删除？', '删除素材', {
        type: 'warning',
        confirmButtonText: '删除',
        confirmButtonClass: 'el-button--danger'
    })
    await deleteSquareAsset({ id: row.id })
    feedback.msgSuccess('已删除')
    await reload()
}

const formatDuration = (sec: number) => {
    const value = Math.max(0, Math.floor(Number(sec) || 0))
    const m = Math.floor(value / 60)
    const s = value % 60
    return `${m}:${String(s).padStart(2, '0')}`
}

onMounted(reload)
</script>

<style lang="scss" scoped>
.side-item {
    @apply w-full mb-1 px-3 py-2 rounded-md flex items-center justify-between gap-2 text-sm text-[#303133];
    &.on {
        @apply bg-[#ECF5FF] text-primary;
    }
    &.off {
        @apply text-[#C0C4CC];
    }
}
</style>
