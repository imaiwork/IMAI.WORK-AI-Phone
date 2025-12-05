import wechatWechatIcon from "@/static/images/common/wechat.png";
import wechatWechatActiveIcon from "@/static/images/common/wechat_s.png";
import redbookIcon from "@/static/images/common/redbook.png";
import redbookActiveIcon from "@/static/images/common/redbook_s.png";
import douyinIcon from "@/static/images/common/douyin.png";
import douyinActiveIcon from "@/static/images/common/douyin_s.png";
import kuaishouIcon from "@/static/images/common/kuaishou.png";
import kuaishouActiveIcon from "@/static/images/common/kuaishou_s.png";
import sphIcon from "@/static/images/common/sph.png";
import sphActiveIcon from "@/static/images/common/sph_s.png";
import { AppTypeEnum } from "@/enums/appEnums";

export function useDevice() {
    // 根据平台类型获取平台图标
    const platformLogo = {
        [AppTypeEnum.WECHAT]: {
            icon: wechatWechatIcon,
            activeIcon: wechatWechatActiveIcon,
        },
        [AppTypeEnum.XHS]: {
            icon: redbookIcon,
            activeIcon: redbookActiveIcon,
        },
        [AppTypeEnum.DOUYIN]: {
            icon: douyinIcon,
            activeIcon: douyinActiveIcon,
        },
        [AppTypeEnum.KUAISHOU]: {
            icon: kuaishouIcon,
            activeIcon: kuaishouActiveIcon,
        },
    };

    // 获取任务状态样式
    const getTaskStatusStyle = (status: number) => {
        switch (status) {
            case 0:
            case 1:
                return "bg-[rgba(0,101,251,0.04)] text-primary";
            case 2:
                return "bg-[rgba(0,192,142,0.1)] text-[#00C08E]";
            case 3:
            case 4:
                return "bg-[rgba(255,36,36,0.1)] text-[#FF2442]";
        }
    };
    const getTaskStatusText = (status: number) => {
        switch (status) {
            case 0:
                return "等待中";
            case 1:
                return "执行中";
            case 2:
                return "执行完成";
            case 3:
                return "执行失败";
            case 4:
                return "中断";
            default:
                return "-";
        }
    };

    return {
        platformLogo,
        getTaskStatusStyle,
        getTaskStatusText,
    };
}
