import { PayStatusEnum } from '@/enums/appEnums'

export class Balance {
    init(name: string, pay: any) {
        pay[name] = this
    }

    async run(options: any) {
        return Promise.resolve(PayStatusEnum.SUCCESS)
    }
}
