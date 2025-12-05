<template>
	<view class="p-[20rpx]">
		<mp-html :content="agreementContent" />
	</view>
</template>

<script setup lang="ts">
import { ref, shallowRef } from "vue";
import { onLoad } from "@dcloudio/uni-app";
import { getPolicy } from "@/api/app";

const agreementType = ref(""); // 协议类型
const agreementContent = ref(""); // 协议内容

const getData = async (type) => {
	const res = await getPolicy({ type });
	agreementContent.value = res.content;
	uni.setNavigationBarTitle({
		title: String(res.title || "协议政策"),
	});
};

onLoad((options: any) => {
	if (options.type) {
		agreementType.value = options.type;
		getData(agreementType.value);
	}
});
</script>

<style lang="scss" scoped></style>
