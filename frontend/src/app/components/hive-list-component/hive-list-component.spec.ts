import { ComponentFixture, TestBed } from '@angular/core/testing';

import { HiveListComponent } from './hive-list-component';

describe('HiveListComponent', () => {
  let component: HiveListComponent;
  let fixture: ComponentFixture<HiveListComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [HiveListComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(HiveListComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
