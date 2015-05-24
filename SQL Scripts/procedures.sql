delimiter //
create procedure voteEntry (in id int, in user varchar(50), in value int) 
begin
	set @prev := 0;
	select V.value into @prev from votes V where V.entryID=id and V.username=user;
	delete from votes where entryID=id and username=user;
	
	IF @prev > 0 THEN
	update Entry set upvotes=upvotes-1 where entryID=id;
	ELSEIF @prev < 0 THEN
	update Entry set downvotes=downvotes-1 where entryID=id;
	END IF;
	
	IF value > 0 THEN
	update Entry set upvotes=upvotes+1 where entryID=id;
	insert into votes values(id,user,value);
	ELSEIF value < 0 THEN
	update Entry set downvotes=downvotes+1 where entryID=id;
	insert into votes values(id,user,value);
	END IF;
end
//
delimiter ;


delimiter //
create procedure closeAccount (in user varchar(50)) 
begin
	delete from approves where username=user;
	delete from collects where username=user;
	delete from Event where username=user;
	delete from follows where username=user;
	delete from votes where username=user;
	update closed_by set username='*CLOSED_ACCOUNT*' where username=user;
	update edits set username='*CLOSED_ACCOUNT*' where username=user;
	update Entry set username='*CLOSED_ACCOUNT*' where username=user;
	
	delete from User where username=user;
end
//
delimiter ;



delimiter //
create procedure changeUsername (in p_old varchar(50), in p_new varchar(50)) 
begin
	SET FOREIGN_KEY_CHECKS = 0;
	
	update approves set username=p_new where username=p_old;
	update collects set username=p_new where username=p_old;
	update Event set username=p_new where username=p_old;
	update follows set username=p_new where username=p_old;
	update votes set username=p_new where username=p_old;
	update closed_by set username=p_new where username=p_old;
	update edits set username=p_new where username=p_old;
	update Entry set username=p_new where username=p_old;
	update User set username=p_new where username=p_old;
	
	SET FOREIGN_KEY_CHECKS = 1;
end
//
delimiter ;




delimiter //
create procedure deleteEntry (in id int) 
begin
	declare finished int default false;
	declare p_row int;
	declare curr cursor for select childEntryID from has_parent where parentEntryID = id;
	declare continue handler for not found set finished = 1;
	
	delete from approves where answerID=id;
	delete from closed_by where entryID=id;
	delete from edits where entryID=id;
	delete from entry_tag where entryID=id;
	delete from votes where entryID=id;
	delete from has_parent where childEntryID=id;
	
	-- Update user types for users
	open curr;
	repeat
		FETCH curr INTO p_row;
		IF finished <> 1 THEN
		call deleteEntry( p_row );
		END IF;
		until finished = 1
	end repeat;
	close curr;
	
	delete from Entry where entryID=id;
end
//
delimiter ;




delimiter //
create procedure updateUserType (in user varchar(50)) 
begin
	-- Find rep points of user
	set @rep := 0;
	select rep into @rep from User where username=user;
	
	-- Find user type that best fits that user
	set @type := 'Newcomer';
	select userType into @type from UserType where repThreshold <= @rep order by repThreshold desc limit 1 ;
	
	update User set userType=@type where username=user;
end
//
delimiter ;





-- foreach alternative inspired from: 
-- http://stackoverflow.com/questions/1775521/mysql-foreach-alternative-for-procedure
delimiter //
create procedure deleteUserType (in type varchar(50)) 
begin
	declare finished int default false;
	declare p_row varchar(50);
	declare curr cursor for select username from User where userType = type;
	declare continue handler for not found set finished = 1;

	-- First get rid of foreign connections
	update User set userType = NULL where userType=type;
	
	-- Delete the user type
	delete from UserType where userType = type;
	
	-- Update user types for users
	open curr;
	repeat
		FETCH curr INTO p_row;
		IF finished <> 1 THEN
		call updateUserType( p_row );
		END IF;
		until finished = 1
	end repeat;
	close curr;
end
//
delimiter ;




-- foreach alternative inspired from: 
-- http://stackoverflow.com/questions/1775521/mysql-foreach-alternative-for-procedure
delimiter //
create procedure updateAllUserTypes () 
begin
	declare finished int default false;
	declare p_row varchar(50);
	declare curr cursor for select username from User where userType <> 'Admin' or userType is NULL;
	declare continue handler for not found set finished = 1;

	-- First get rid of foreign connections
	update User set userType = NULL where userType <> 'Admin';
	
	-- Update user types for users
	open curr;
	repeat
		FETCH curr INTO p_row;
		IF finished <> 1 THEN
		call updateUserType( p_row );
		END IF;
		until finished = 1
	end repeat;
	close curr;
end
//
delimiter ;




delimiter //
create procedure canEditEntry( in p_entryID int, in p_user varchar(50), out result int )
begin
	select count(*) into result
	from ((select E.username as name from Entry E where E.username=p_user and entryID=p_entryID)
	union
	(select U.username as name from User U natural join user_permission where U.username=p_user
	 and permission_type='edit_all_entries')) as r;
end
//
delimiter ;



delimiter //
create procedure canCloseEntry( in p_entryID int, in p_user varchar(50), out result int )
begin
	select count(*) into result
	from ((select E.username as name from Entry E where E.username=p_user and entryID=p_entryID and E.username in(
	select U1.username as name from User U1 natural join user_permission where U1.username=p_user
	 and permission_type='close_own_entry' ))
	union
	(select U.username as name from User U natural join user_permission where U.username=p_user
	 and permission_type='close_all_entries')) as r;
end
//
delimiter ;





delimiter //
create procedure canDeleteEntry( in p_entryID int, in p_user varchar(50), out result int )
begin
	select count(*) into result
	from ((select E.username as name from Entry E where E.username=p_user and entryID=p_entryID and E.username in(
	select U1.username as name from User U1 natural join user_permission where U1.username=p_user
	 and permission_type='delete_own_entry' ))
	union
	(select U.username as name from User U natural join user_permission where U.username=p_user
	 and permission_type='delete_all_entries')) as r;
end
//
delimiter ;




delimiter //
create function canEditEntry( p_entryID int, p_user varchar(50) )
	returns integer READS SQL DATA
begin
	declare result integer;
	select count(*) into result
	from ((select E.username as name from Entry E where E.username=p_user and entryID=p_entryID)
	union
	(select U.username as name from User U natural join user_permission where U.username=p_user
	 and permission_type='edit_all_entries')) as r;
	return result;
end
//
delimiter ;




delimiter //
create trigger newUserTrigger after insert on User
for each row
begin
	call updateUserType( new.username );
end;
//
delimiter ;