import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard';
import { RouterModule, Routes } from '@angular/router';
import { DashboardRoutingModule } from './dashboard-routing.module';



@NgModule({
  declarations: [ ],
  imports: [
    RouterModule,
    CommonModule,
    DashboardRoutingModule,
    DashboardComponent
  ]
})
export class DashboardModule { }
