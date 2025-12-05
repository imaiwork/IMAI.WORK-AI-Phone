<!-- 卡密设置 -->
<template>
    <div class="user-setup">
        <el-form ref="formRef" :model="formData" label-width="120px">
            <el-card shadow="never" class="!border-none mt-4">
                <div class="font-medium mb-7">卡密设置</div>
                <el-form-item label="功能状态">
                    <div>
                        <el-switch v-model="formData.is_open" :active-value="1" :inactive-value="0" />
                        <div class="form-tips">默认关闭，开启的话在前台可以使用该功能</div>
                    </div>
                </el-form-item>
            </el-card>
        </el-form>
        <footer-btns>
            <el-button v-perms="['cardcode.cardCode/setConfig']" type="primary" :loading="isLock" @click="lockFn"
                >保存</el-button
            >
        </footer-btns>
    </div>
</template>

<script lang="ts" setup name="redeemCodeSetup">
import { cardcodeConfigGet, cardcodeConfigSet } from "@/api/marketing/redeem_code";
import { useLockFn } from "@/hooks/useLockFn";
const formData = reactive({
    is_open: 0,
});
const getData = async () => {
    const data = await cardcodeConfigGet();
    Object.keys(formData).map((item) => {
        //@ts-ignore
        formData[item] = data[item];
    });
};
const handleSubmit = async () => {
    await cardcodeConfigSet(formData);
    getData();
};

const { lockFn, isLock } = useLockFn(handleSubmit);

getData();
</script>

<style lang="scss" scoped></style>
