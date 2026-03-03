# TODO: Update VehicleTypeController.php for Database Changes

## Tasks
- [ ] Update index method: Change body_type param to type_body_id, remove year param, update validation and query
- [ ] Update store method: Change body_type to type_body_id in validation and create
- [ ] Update update method: Change body_type to type_body_id in validation and update
- [ ] Update bodyTypes method: Fetch from VehicleTypeBody instead of VehicleType
- [ ] Update details method: Remove year filter
- [ ] Update OpenAPI annotations: Remove year params, change body_type to type_body_id
- [ ] Test the API endpoints after changes
