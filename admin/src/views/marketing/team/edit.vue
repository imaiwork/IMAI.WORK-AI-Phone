<template>
    <popup
        ref="popupRef"
        async
        width="500px"
        title="创建团队"
        :confirm-loading="isLock"
        @confirm="lockFn"
        @close="close">
        <div>
            <el-form :model="formData" :rules="rules" ref="formRef" label-width="110px">
                <el-form-item label="团队名称" prop="name">
                    <el-input v-model="formData.name" placeholder="请输入团队名称" maxlength="30" />
                </el-form-item>
                <el-form-item label="团队归属用户" prop="owner_id">
                    <el-select
                        v-model="formData.owner_id"
                        placeholder="请输入用户信息"
                        filterable
                        remote
                        reserve-keyword
                        :remote-method="remoteMethodUser"
                        :loading="userLoading"
                        clearable>
                        <el-option
                            v-for="item in userList"
                            :key="item.id"
                            :label="`${item.nickname}（${item.account}）`"
                            :value="item.id" />
                    </el-select>
                    <div class="form-tips">该用户将成为团队主管理员</div>
                </el-form-item>
                <el-form-item label="备注" prop="remark">
                    <el-input v-model="formData.remark" type="textarea" :rows="3" placeholder="请输入备注" />
                </el-form-item>
            </el-form>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { teamCreate } from '@/api/team'
import { getUserList } from '@/api/consumer'
import { useLockFn } from '@/hooks/useLockFn'
import { type FormInstance } from 'element-plus'

const emit = defineEmits<{
    (e: 'close'): void
    (e: 'success'): void
}>()

const formRef = shallowRef<FormInstance>()
const formData = reactive<any>({
    name: '',
    owner_id: '',
    remark: '',
})
const rules = {
    name: [{ required: true, message: '请输入团队名称' }],
    owner_id: [{ required: true, message: '请选择团队归属用户' }],
}

const popupRef = ref()

const userList = ref<any[]>([])
const userLoading = ref(false)

const remoteMethodUser = async (query: string) => {
    try {
        userLoading.value = true
        const { lists } = await getUserList({ keyword: query })
        userList.value = lists
    } finally {
        userLoading.value = false
    }
}

const submit = async () => {
    await formRef.value?.validate()
    await teamCreate(formData)
    close()
    emit('success')
}

const open = () => {
    popupRef.value?.open()
}

const close = () => {
    emit('close')
}

const { lockFn, isLock } = useLockFn(submit)

defineExpose({
    open,
})
</script>

<style scoped></style>
