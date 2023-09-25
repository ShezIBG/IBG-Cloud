import { Component, OnInit, Input } from '@angular/core';
import { Md5 } from 'ts-md5/dist/md5';

@Component({
	selector: 'app-gravatar',
	template: `<img src="https://www.gravatar.com/avatar/{{hash}}?s={{size}}&d=identicon&r=g" style="border-radius: 50%; border: 3px solid #d4d7db;">`
})
export class GravatarComponent implements OnInit {

	hash = '';

	@Input() email;
	@Input() size = 32;

	ngOnInit() {
		this.hash = Md5.hashStr(('' + this.email).trim().toLowerCase()) as string;
	}

}
