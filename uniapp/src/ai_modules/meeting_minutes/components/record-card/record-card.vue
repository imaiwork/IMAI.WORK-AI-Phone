<template>
	<view
		class="bg-white rounded-2xl border border-solid border-[#e8eaf2]"
		@click="handleItem(item)">
		<view class="flex flex-col items-center">
			<view
				class="rounded-2xl border-[8rpx] border-solid border-white w-full h-auto">
				<view class="content-box">
					<view
						v-if="item.status == TurnStatus.SUCCESS"
						class="absolute inset-0">
						<template v-if="getResult(item)">
							<view class="success-box">
								<image
									src="@/ai_modules/meeting_minutes/static/images/common/tps.png"
									class="absolute top-[40rpx] left-[48rpx] w-[80rpx] h-[54rpx]">
								</image>
							</view>
							<view
								class="absolute top-0 left-0 pt-[32rpx] px-[48rpx] pb-[28rpx] z-2 w-full h-full">
								<view
									class="text-[#585a73] text-xs indent-8 h-[120rpx] my-[20rpx] leading-[40rpx] w-full line-clamp-3">
									{{ getResult(item) }}
								</view>
								<view
									class="h-[32rpx] absolute bottom-[52rpx] left-[48rpx] overflow-hidden mt-1"
									style="width: calc(100% - 96rpx)">
									<image
										src="@/ai_modules/meeting_minutes/static/images/common/audio_spectrum.png"
										class="h-[16px] max-w-none">
									</image>
								</view>
							</view>
							<view
								class="absolute rounded bg-[rgba(0,0,0,0.27)] right-[40rpx] bottom-[48rpx] flex items-center justify-center h-[40rpx] py-[8rpx] px-1">
								<span class="text-xs text-white">
									{{ getDuration(item) }}
								</span>
							</view>
						</template>
						<view v-else class="success-empty-box">
							<image
								src="@/ai_modules/meeting_minutes/static/icons/audio_mic.svg"
								class="w-[102rpx] h-[102rpx]"></image>
						</view>
					</view>
					<view
						class="absolute top-0 left-0 w-full h-full flex items-center justify-center"
						v-else-if="item.status == TurnStatus.ERROR">
						<image
							src="@/ai_modules/meeting_minutes/static/images/common/error.png"
							class="w-[136rpx] h-[136rpx]" />
					</view>
					<view
						class="ing-box"
						v-else-if="
							item.status == TurnStatus.ING ||
							item.status == TurnStatus.WAITING
						">
						<image
							src="@/ai_modules/meeting_minutes/static/images/common/audio_loading.gif"
							class="w-[136rpx] h-[136rpx]">
						</image>
					</view>
				</view>
				<view class="w-full h-[132rpx] py-3 px-4">
					<view
						class="text-ellipsis whitespace-nowrap overflow-hidden font-semibold">
						{{ formatName(item.name) }}
					</view>
					<view class="mt-2">
						<template v-if="item.status == TurnStatus.SUCCESS">
							<view
								class="flex flex-wrap gap-2 overflow-hidden max-h-[40rpx]"
								v-if="
									getTags(item) && getTags(item).length > 0
								">
								<view
									v-for="(tag, index) in getTags(item)"
									:key="index"
									class="text-xs text-[#8f91a8] px-2 flex justify-center items-center bg-[#f7f8fc] h-[40rpx] rounded">
									{{ tag }}
								</view>
							</view>
							<view class="text-xs text-[#8f91a8]" v-else>
								内容为空
							</view>
						</template>
						<view
							class="text-xs text-[#8f91a8]"
							v-else-if="
								item.status == TurnStatus.ING ||
								item.status == TurnStatus.WAITING
							">
							转写中
						</view>
					</view>
				</view>
				<view
					class="px-[20rpx] pb-[20rpx] flex items-center justify-between gap-2">
					<view class="flex items-center gap-2 justify-end grow">
						<view class="text-[#B2B5B6] text-xs">
							{{ formatDate(item.create_time) }}
						</view>
						<view class="px-2 py-1" @click.stop="handleMore">
							<u-icon
								name="more-dot-fill"
								size="28"
								color="#B2B5B6"></u-icon>
						</view>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script setup lang="ts">
import { formatAudioTime } from "@/utils/util";
import { TurnStatus } from "../../enums";
import useHandleApi from "../../hooks/useHandleApi";

const props = defineProps({
	item: {
		type: Object,
		default: () => {},
	},
});

const emit = defineEmits(["delete-success", "again-success"]);

const {
	handleAgain,
	handleDelete,
	formatName,
	formatDate,
	handleItem,
	getTags,
	getDuration,
	getResult,
} = useHandleApi();

const handleMore = () => {
	const { id, status } = props.item;
	const items = ["删除"];
	if (status === TurnStatus.ERROR) {
		items.unshift("重试");
	}
	uni.showActionSheet({
		itemList: items,
		success: async (res) => {
			const { tapIndex } = res;
			if (status === TurnStatus.ERROR) {
				if (tapIndex === 0) {
					await handleAgain(id);
					emit("again-success", id);
				}
				if (tapIndex === 1) {
					await handleDelete(id);
					emit("delete-success", id);
				}
			} else {
				if (tapIndex === 0) {
					await handleDelete(id);
					emit("delete-success", id);
				}
			}
		},
	});
};
</script>

<style scoped lang="scss">
.content-box {
	@apply w-full h-full pt-[46.25%] relative;
}
.success-box {
	@apply w-full h-full relative bg-cover bg-no-repeat rounded-xl;
	background-image: url("@/ai_modules/meeting_minutes/static/images/common/tps_bg.jpg");
	background-position: left top;
	background-size: cover;
}
.success-empty-box {
	background: radial-gradient(
		50% 50% at 50% 50%,
		rgb(243, 242, 255) 0%,
		rgb(247, 246, 252) 98%
	);
	@apply w-full h-full flex items-center justify-center rounded-xl;
}
.ing-box {
	background: radial-gradient(
		50% 50% at 50% 50%,
		rgb(243, 242, 255) 0%,
		rgb(247, 246, 252) 98%
	);
	@apply absolute w-full h-full top-0 left-0 flex items-center justify-center rounded-xl;
}

.skeleton-container {
	display: flex;
	flex-direction: column;
	gap: 16rpx;
}
.skeleton-item {
	height: 40rpx;
	border-radius: 10rpx;
	background: linear-gradient(90deg, #f0f2f5 25%, #e6e8eb 37%, #f0f2f5 63%);
	background-size: 400% 100%;
	animation: skeleton-loading 1.4s ease infinite;
}
.time-container {
	background: linear-gradient(
		117.59deg,
		#5f90d9 0%,
		#94c6ff 38.9%,
		#6eb0f8 74.98%,
		#71a5f8 100%
	);
	@apply h-[94rpx] rounded-lg mt-2 relative z-20 flex items-center justify-between px-[32rpx];
	&.is-container {
		box-shadow: 0px -20px 40px 10px rgba(255, 255, 255, 1);
		@apply -mt-[48rpx];
	}
}
@keyframes skeleton-loading {
	0% {
		background-position: 100% 50%;
	}
	100% {
		background-position: 0 50%;
	}
}
</style>
