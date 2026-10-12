<template>
    <el-drawer
        v-model="visible"
        :title="isEdit ? '编辑分组' : '新建分组'"
        size="400px"
        destroy-on-close
        @close="handleClose">
        <el-form label-position="top">
            <el-form-item required>
                <template #label>分组名称</template>
                <el-input v-model="form.name" maxlength="16" show-word-limit placeholder="例：装修 · 施工现场" />
                <p class="mt-1 text-xs text-[#86909C]">客户在广场里按分组筛选，分组名就是他看到的筛选标签</p>
            </el-form-item>
            <el-form-item label="备注">
                <el-input
                    v-model="form.desc"
                    type="textarea"
                    :rows="2"
                    maxlength="60"
                    show-word-limit
                    placeholder="例：工地、水电、瓷砖铺贴等施工过程空镜" />
                <p class="mt-1 text-xs text-[#86909C]">只在后台看，客户端不显示</p>
            </el-form-item>
            <el-form-item label="上架">
                <el-switch v-model="form.enabled" :active-value="1" :inactive-value="0" />
                <p class="mt-1 text-xs text-[#86909C]">
                    管的是整个分组：关掉后组里全部素材一起对客户隐藏，已经加过的客户那边显示「已下架」
                </p>
            </el-form-item>
            <el-form-item label="排序">
                <el-input-number v-model="form.sort" :controls="false" class="!w-[120px]" />
                <p class="mt-1 text-xs text-[#86909C]">数字小的排前面，也是客户广场里分组标签的顺序</p>
            </el-form-item>
            <el-alert
                v-if="isEdit"
                type="warning"
                :closable="false"
                show-icon
                title="删除这个分组会把组里的素材一起删掉，删了找不回。只想暂时不让客户看到，关掉上面的「上架」就行" />
        </el-form>
        <template #footer>
            <div class="flex items-center gap-2">
                <el-button v-if="isEdit" type="danger" link class="mr-auto" @click="handleDelete">
                    删除这个分组
                </el-button>
                <el-button @click="visible = false">取消</el-button>
                <el-button type="primary" :loading="saving" @click="handleSave">保存</el-button>
            </div>
        </template>
    </el-drawer>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { ElMessageBox } from 'element-plus'
import feedback from '@/utils/feedback'
import { addSquareGroup, deleteSquareGroup, updateSquareGroup } from '@/api/ai_application/square'

export interface SquareGroupRow {
    id: number
    name: string
    desc: string
    enabled: number
    sort: number
    asset_count?: number
}

const props = defineProps<{
    modelValue: boolean
    group: SquareGroupRow | null
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'saved'): void
}>()

const visible = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value)
})

const isEdit = computed(() => !!props.group?.id)
const saving = ref(false)
const form = reactive({
    name: '',
    desc: '',
    enabled: 1,
    sort: 0
})

watch(
    () => props.modelValue,
    (open) => {
        if (!open) return
        form.name = props.group?.name || ''
        form.desc = props.group?.desc || ''
        form.enabled = props.group ? Number(props.group.enabled) : 1
        form.sort = props.group?.sort || 0
    }
)

const handleClose = () => {
    emit('update:modelValue', false)
}

const handleSave = async () => {
    if (!form.name.trim()) {
        feedback.msgWarning('分组名称不能为空')
        return
    }
    saving.value = true
    try {
        const payload = { ...form, name: form.name.trim() }
        if (isEdit.value && props.group) {
            await updateSquareGroup({ id: props.group.id, ...payload })
        } else {
            await addSquareGroup(payload)
        }
        feedback.msgSuccess('已保存')
        emit('saved')
        visible.value = false
    } catch (error: any) {
        feedback.msgError(error || '保存失败')
    } finally {
        saving.value = false
    }
}

const handleDelete = async () => {
    if (!props.group) return
    await ElMessageBox.confirm('删除后组内素材一起删掉且不可恢复，确定删除这个分组？', '删除分组', {
        type: 'warning',
        confirmButtonText: '删除',
        confirmButtonClass: 'el-button--danger'
    })
    await deleteSquareGroup({ id: props.group.id })
    feedback.msgSuccess('已删除')
    emit('saved')
    visible.value = false
}
</script>
