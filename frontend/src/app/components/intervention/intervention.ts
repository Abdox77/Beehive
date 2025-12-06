import { Component, EventEmitter, Input, Output } from '@angular/core';
import { HttpClient } from '@angular/common/http';

const BACKEND_URL = 'http://localhost:8000';

export interface InterventionData {

}

@Component({
  selector: 'app-intervention',
  imports: [],
  templateUrl: './intervention.html',
  styleUrl: './intervention.css'
})
export class Intervention {
    @Input() hiveId: number = 0;
    @Output() closePopup = new EventEmitter<void>();

    constructor (
        private http: HttpClient
    ) { }

    onInterventionAdd(data: InterventionData) {

    }

    onClose() {
        this.closePopup.emit();
    }
}
