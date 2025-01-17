<!-- Update User Modal -->
<div class="modal fade" id="updateUserModal" tabindex="-1" aria-labelledby="updateUserModalLabel" aria-hidden="true">
    <div class="modal-dialog p-3">
        <div class="modal-content p-3">
            <!-- Modal Header with no border -->
            <div class="modal-header border-0">
                <h5 class="modal-title" id="updateUserModalLabel">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="updateUserForm">
                    <input type="hidden" name="id" id="updateUserId">

                    <div class="mb-3">
                        <label for="updateEmail" class="form-label">Emaiul</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-0 border-bottom border-dark">
                                <i class="fa-regular fa-envelope"></i>
                            </span>
                            <input type="email" name="email" id="updateEmail" class="form-control border-0 border-bottom border-dark" placeholder="Enter your email" disabled>
                        </div>
                        <p class="error text-danger"></p> <!-- Error message container -->
                    </div>

                    <div class="mb-3">
                        <label for="updateUsername" class="form-label">New Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-0 border-bottom border-dark">
                                <i class="fa-regular fa-user"></i>
                            </span>
                            <input type="text" name="username" id="updateUsername" class="form-control border-0 border-bottom border-dark" disabled>
                        </div>
                        <p class="error text-danger"></p> <!-- Error message container -->
                    </div>

                    

                    <div class="mb-3">
                        <label for="updateRoleSelect" class="form-label">Select Role</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-0 border-bottom border-dark">
                                <i class="fa-regular fa-user"></i>
                            </span>
                            <select class="form-select border-0 border-bottom border-dark" id="updateRoleSelect" name="role" required>
                                <option selected disabled>Select Role</option>
                                <option value="owner">Owner</option>
                                <option value="staff">Staff</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn_color btn-primary mx-auto w-100 m-5 p-3" style="background-color: #FF902B; border-radius: 30px; border: none;">
                        Update User
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
