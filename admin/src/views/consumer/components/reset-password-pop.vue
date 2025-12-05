<template>
    <Popup title="重置密码" ref="popRef" async @confirm="submit" @close="$emit('close')">
        <div>
            <el-form @submit.prevent>
                <el-form-item label="密码设置">
                    <el-input type="password" v-model="formData.password" show-password />
                </el-form-item>
            </el-form>
        </div>
    </Popup>
</template>

<script setup lang="ts">
import { resetPassword } from '@/api/consumer'

const emit = defineEmits(['close'])

const formData = reactive({
    password: '',
    id: 0
})

//弹框ref
const popRef = shallowRef()

//打开弹框
const open = (id: number) => {
    formData.id = id
    popRef.value.open()
}

const submit = async () => {
    await resetPassword({ ...formData })
    popRef.value.close()
}

defineExpose({ open })
</script>

<style scoped lang="scss"></style>
