import { Injectable } from '@angular/core';

import { AppService } from './../app.service';

@Injectable()
export class ModuleService {

	constructor(protected app: AppService) { }

}
