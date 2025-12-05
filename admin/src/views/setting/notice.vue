<template>
    <div>
        <el-card shadow="never" class="!border-none">
            <div class="font-medium">公告设置</div>
            <div class="mt-4">
                <el-form label-width="120px">
                    <el-form-item label="公告弹框">
                        <div>
                            <div class="flex items-center">
                                <el-switch
                                    :active-value="1"
                                    :inactive-value="0"
                                    v-model="formData.is_bulletin"
                                />
                                <div class="ml-2 font-medium">
                                    {{ formData.is_bulletin == 1 ? '开启' : '关闭' }}
                                </div>
                            </div>
                            <div class="form-tips">用户每天首次进入站点会触发弹框</div>
                        </div>
                    </el-form-item>
                    <el-form-item label="公告内容">
                        <Editor v-model="formData.bulletin_content" height="500px" width="500px" />
                    </el-form-item>
                </el-form>
            </div>
        </el-card>
        <FooterBtns>
            <el-button type="primary" @click="setData">保存</el-button>
        </FooterBtns>
    </div>
</template>

<script setup lang="ts">
import { getNoticeSet, setNoticeSet } from '@/api/setting/notice'

const formData = reactive({
    is_bulletin: 0,
    bulletin_content: ''
})

const getData = async () => {
    const res = await getNoticeSet()
    Object.keys(formData).map((item) => {
        //@ts-ignore
        formData[item] = res[item]
    })
}

const setData = async () => {
    await setNoticeSet({ ...formData })
    getData()
}

onMounted(() => {
    getData()
})
</script>

<style scoped lang="scss"></style>
