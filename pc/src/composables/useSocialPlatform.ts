import { AppTypeEnum } from "@/enums/appEnums";
import RedBookIcon from "@/assets/images/redbook_icon.png";
import DouyinIcon from "@/assets/images/douyin_icon.png";
import KuaishouIcon from "@/assets/images/kuaishou_icon.png";
import WechatIcon from "@/assets/images/wechat_icon.png";
import SphIcon from "@/assets/images/sph_icon.png";

export const useSocialPlatform = () => {
    // 社媒平台列表
    const socialPlatformList = [
        { name: "小红书", icon: RedBookIcon, type: AppTypeEnum.XHS, show: true },
        { name: "视频号", icon: SphIcon, type: AppTypeEnum.SPH, show: true },
        { name: "抖音", icon: DouyinIcon, type: AppTypeEnum.DOUYIN, show: true },
        { name: "快手", icon: KuaishouIcon, type: AppTypeEnum.KUAISHOU, show: true },
    ];

    // 当前社媒平台
    const currentSocialPlatform = ref<AppTypeEnum>(AppTypeEnum.XHS);

    // 获取当前平台
    const getPlatform = (type: AppTypeEnum) => {
        return socialPlatformList.find((item) => item.type == type);
    };

    return {
        socialPlatformList,
        currentSocialPlatform,
        getPlatform,
    };
};
